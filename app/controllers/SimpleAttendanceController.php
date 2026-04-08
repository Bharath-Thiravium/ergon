<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/TimezoneHelper.php';
require_once __DIR__ . '/../helpers/LocationHelper.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';

class SimpleAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
        $this->ensureAttendanceTable($this->db);
    }
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        $userId = $_SESSION['user_id'];
        $selectedDate = $_GET['date'] ?? TimezoneHelper::getCurrentDate();
        $filter = $_GET['filter'] ?? 'today';
        
        // Query to get all users with their attendance data for the selected date
        $roleFilter = '';
        $roleParams = [];
        if ($role === 'user') {
            $roleFilter = "AND u.id = ?";
            $roleParams[] = $userId;
        } elseif ($role === 'admin') {
            // Include both admin's own attendance and employee attendance
            $roleFilter = "AND (u.role IN ('user') OR u.id = ?)";
            $roleParams[] = $userId;
        } else {
            $roleFilter = "AND u.role IN ('admin', 'user')";
        }
        
        // Use date filter if provided, otherwise use time-based filter
        $dateParams = [];
        if (isset($_GET['date']) && $_GET['date'] !== TimezoneHelper::getCurrentDate()) {
            $dateCondition = "DATE(a.check_in) = ?";
            $dateParams[] = $selectedDate;
        } else {
            $dateCondition = $this->getDateCondition($filter);
        }
        
        // Build params in correct order: date params (used in JOIN) come before role params (used in WHERE)
        $queryParams = array_merge($dateParams, $roleParams);
        
        $stmt = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                a.id as attendance_id,
                a.check_in,
                a.check_out,
                a.project_id,
                CASE 
                    WHEN p.name IS NOT NULL THEN p.name
                    WHEN a.check_in IS NOT NULL AND a.project_id IS NULL THEN (SELECT location_title FROM settings LIMIT 1)
                    ELSE '----'
                END as project_name,
                CASE 
                    WHEN p.place IS NOT NULL THEN p.place
                    WHEN a.check_in IS NOT NULL AND a.project_id IS NULL THEN (SELECT office_address FROM settings LIMIT 1)
                    ELSE 'Office'
                END as location_display,
                CASE 
                    WHEN a.check_in IS NOT NULL THEN 'Present'
                    ELSE 'Absent'
                END as status,
                COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00') as check_in_time,
                COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00') as check_out_time,
                CASE 
                    WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                        CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                               TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                    WHEN a.check_in IS NOT NULL THEN
                        CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, NOW()), 'h ',
                               TIMESTAMPDIFF(MINUTE, a.check_in, NOW()) % 60, 'm')
                    ELSE '0h 0m'
                END as working_hours
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND {$dateCondition}
            LEFT JOIN projects p ON a.project_id = p.id
            WHERE u.status = 'active' {$roleFilter}
            ORDER BY u.role DESC, u.name
        ");
        $stmt->execute($queryParams);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by role for owner and admin view
        if ($role === 'owner' || $role === 'admin') {
            $attendance = ['admin' => [], 'user' => []];
            foreach ($records as $record) {
                $userRole = $record['role'] === 'admin' ? 'admin' : 'user';
                $attendance[$userRole][] = $record;
            }
            $isGrouped = true;
        } else {
            $attendance = $records;
            $isGrouped = false;
        }
        
        // Calculate stats
        $stats = ['total_hours' => 0, 'total_minutes' => 0, 'present_days' => 0];
        if (!empty($records)) {
            if ($role === 'user') {
                $stats = $this->calculateUserStats($records);
            } elseif ($role === 'admin') {
                // Calculate stats for admin's own records only
                $adminRecords = array_filter($records, function($record) {
                    return $record['user_id'] == $_SESSION['user_id'];
                });
                $stats = $this->calculateUserStats($adminRecords);
            }
        }
        
        $this->view('attendance/index', [
            'attendance' => $attendance,
            'stats' => $stats,
            'current_filter' => $filter,
            'selected_date' => $selectedDate,
            'user_role' => $role,
            'active_page' => 'attendance',
            'is_grouped' => $isGrouped
        ]);
    }
    
    public function status() {
        $this->requireAuth();
        session_write_close(); // Release session lock for concurrent requests
        
        header('Content-Type: application/json');
        
        try {
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $onLeave = false;
            try {
                $stmt = $this->db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                $stmt->execute([$_SESSION['user_id']]);
                $onLeave = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                $onLeave = false;
            }
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'on_leave' => $onLeave
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function manual() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = intval($_POST['user_id']);
                $checkIn = $_POST['check_in'] ?? null;
                $checkOut = $_POST['check_out'] ?? null;
                $date = $_POST['date'] ?? date('Y-m-d');
                
                // Always check for existing record first
                $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing record
                    $stmt = $this->db->prepare("UPDATE attendance SET check_in = ?, check_out = ?, manual_entry = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $date . ' ' . $checkIn,
                        $checkOut ? $date . ' ' . $checkOut : null,
                        $existing['id']
                    ]);
                } else {
                    // Create new record only if none exists
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name, manual_entry, created_at) VALUES (?, ?, ?, 'present', 'Manual Entry', 1, NOW())");
                    $stmt->execute([
                        $userId,
                        $date . ' ' . $checkIn,
                        $checkOut ? $date . ' ' . $checkOut : null
                    ]);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Manual attendance recorded']);
                exit;
                
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }
    
    public function clock() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $type = $_POST['type'] ?? '';
                $userId = $_SESSION['user_id'];
                $latitude = $_POST['latitude'] ?? null;
                $longitude = $_POST['longitude'] ?? null;
                
                if ($type === 'in') {
                    $currentDate = TimezoneHelper::getCurrentDate();
                    $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                    $stmt->execute([$userId, $currentDate]);
                    
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'error' => 'Already clocked in today']);
                        exit;
                    }
                    
                    // Validate GPS coordinates
                    if (!$latitude || !$longitude) {
                        echo json_encode(['success' => false, 'error' => 'GPS location is required for attendance']);
                        exit;
                    }
                    
                    $currentTime = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
                    
                    // Check for project match based on GPS coordinates
                    $projectId = $this->getProjectIdByGPS($latitude, $longitude);
                    
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, project_id, check_in, latitude, longitude, location_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$userId, $projectId, $currentTime, $latitude, $longitude, 'Office', $currentTime]);
                    
                    echo json_encode([
                        'success' => $result,
                        'message' => $result ? 'Clocked in successfully' : 'Failed to clock in'
                    ]);
                } elseif ($type === 'out') {
                    $currentTime = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
                    $currentDate = TimezoneHelper::getCurrentDate();
                    
                    $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL");
                    $stmt->execute([$userId, $currentDate]);
                    $attendance = $stmt->fetch();
                    
                    if (!$attendance) {
                        echo json_encode(['success' => false, 'error' => 'No clock in record found for today']);
                        exit;
                    }
                    
                    $stmt = $this->db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                    $result = $stmt->execute([$currentTime, $attendance['id']]);
                    
                    echo json_encode([
                        'success' => $result,
                        'message' => $result ? 'Clocked out successfully' : 'Failed to clock out'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        } else {
            // Show clock page
            $todayAttendance = null;
            $onLeave = false;
            
            try {
                $currentDate = TimezoneHelper::getCurrentDate();
                
                $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$_SESSION['user_id'], $currentDate]);
                $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
                
                try {
                    $stmt = $this->db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                    $stmt->execute([$_SESSION['user_id']]);
                    $onLeave = $stmt->fetch() ? true : false;
                } catch (Exception $e) {
                    $onLeave = false;
                }
                
            } catch (Exception $e) {
                error_log('Clock page error: ' . $e->getMessage());
            }
            
            $attendanceStatus = [
                'has_clocked_in' => $todayAttendance && $todayAttendance['check_in'] ? true : false,
                'has_clocked_out' => $todayAttendance && $todayAttendance['check_out'] ? true : false,
                'on_leave' => $onLeave,
                'is_completed' => $todayAttendance && $todayAttendance['check_in'] && $todayAttendance['check_out'] ? true : false
            ];
            
            $this->view('attendance/clock', [
                'today_attendance' => $todayAttendance,
                'on_leave' => $onLeave,
                'attendance_status' => $attendanceStatus,
                'active_page' => 'attendance'
            ]);
        }
    }
    
    private function ensureAttendanceTable($db) {
        try {
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                project_id INT NULL,
                check_in DATETIME NOT NULL,
                check_out DATETIME NULL,
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                location_name VARCHAR(255) DEFAULT 'Office',
                status VARCHAR(20) DEFAULT 'present',
                manual_entry TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_project_id (project_id),
                INDEX idx_check_in_date (check_in)
            )", "Create table");
            
            // Add latitude and longitude columns if they don't exist
            try {
                $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'latitude'");
                if ($stmt->rowCount() == 0) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10, 8) NULL", "Add latitude column");
                }
            } catch (Exception $e) {
                error_log('Add latitude column error: ' . $e->getMessage());
            }
            
            try {
                $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'longitude'");
                if ($stmt->rowCount() == 0) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11, 8) NULL", "Add longitude column");
                }
            } catch (Exception $e) {
                error_log('Add longitude column error: ' . $e->getMessage());
            }
            
            // Add project_id column if it doesn't exist
            try {
                $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'project_id'");
                if ($stmt->rowCount() == 0) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN project_id INT NULL", "Add project_id column");
                }
            } catch (Exception $e) {
                error_log('Add project_id column error: ' . $e->getMessage());
            }
            
            // Add manual_entry column if it doesn't exist
            try {
                $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'manual_entry'");
                if ($stmt->rowCount() == 0) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN manual_entry TINYINT(1) DEFAULT 0", "Add manual_entry column");
                }
            } catch (Exception $e) {
                error_log('Add manual_entry column error: ' . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log('ensureAttendanceTable error: ' . $e->getMessage());
        }
    }
    
    private function getDateCondition($filter) {
        switch ($filter) {
            case 'today':
                return "DATE(a.check_in) = CURDATE()";
            case 'week':
                return "DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'two_weeks':
                return "DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
            case 'month':
                return "DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            default:
                return "DATE(a.check_in) = CURDATE()";
        }
    }
    
    private function getProjectIdByGPS($latitude, $longitude) {
        try {
            // First check project locations
            $stmt = $this->db->prepare("SELECT id, latitude, longitude, checkin_radius FROM projects WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'active'");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($projects as $project) {
                if ($project['latitude'] != 0 && $project['longitude'] != 0) {
                    $distance = $this->calculateDistance($latitude, $longitude, $project['latitude'], $project['longitude']);
                    
                    if ($distance <= $project['checkin_radius']) {
                        return $project['id'];
                    }
                }
            }
            
            // If no project match, check system settings (main office)
            $stmt = $this->db->prepare("SELECT base_location_lat, base_location_lng, attendance_radius, location_title FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings && $settings['base_location_lat'] != 0 && $settings['base_location_lng'] != 0) {
                $distance = $this->calculateDistance($latitude, $longitude, $settings['base_location_lat'], $settings['base_location_lng']);
                
                if ($distance <= $settings['attendance_radius']) {
                    // Use settings-based attendance without project_id
                    return null; // No project_id for settings-based attendance
                }
            }
        } catch (Exception $e) {
            error_log('GPS project matching error: ' . $e->getMessage());
        }
        
        return null; // No match found
    }
    
    private function getOrCreateMainOfficeProject() {
        try {
            // Check if main office project exists
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = 'Main Office' AND status = 'active'");
            $stmt->execute();
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project) {
                return $project['id'];
            }
            
            // Create main office project if it doesn't exist
            $stmt = $this->db->prepare("SELECT base_location_lat, base_location_lng, attendance_radius, location_title FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                $stmt = $this->db->prepare("
                    INSERT INTO projects (name, description, latitude, longitude, checkin_radius, status, place, created_at) 
                    VALUES (?, 'Main office location for general attendance', ?, ?, ?, 'active', ?, NOW())
                ");
                $stmt->execute([
                    $settings['location_title'] ?: 'Main Office',
                    $settings['base_location_lat'],
                    $settings['base_location_lng'],
                    $settings['attendance_radius'],
                    $settings['location_title'] ?: 'Main Office'
                ]);
                
                return $this->db->lastInsertId();
            }
        } catch (Exception $e) {
            error_log('Main office project creation error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c; // Distance in meters
    }
    
    private function calculateUserStats($attendance) {
        $totalMinutes = 0;
        $presentDays = 0;
        
        foreach ($attendance as $record) {
            // Count as present if there's a check_in (regardless of check_out)
            if ($record['check_in'] && $record['status'] === 'Present') {
                $presentDays++;
                
                $endTime = $record['check_out'] ?: (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
                $minutes = max(0, (strtotime($endTime) - strtotime($record['check_in'])) / 60);
                $totalMinutes += $minutes;
            }
        }
        
        $totalHours = (int)floor($totalMinutes / 60);
        $remainingMinutes = (int)((int)$totalMinutes % 60);
        
        return [
            'total_hours' => $totalHours,
            'total_minutes' => $remainingMinutes,
            'present_days' => $presentDays
        ];
    }
    
    public function delete() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $attendanceId = intval($_POST['id'] ?? 0);
                
                if ($attendanceId <= 0) {
                    throw new Exception('Invalid attendance ID');
                }
                
                $stmt = $this->db->prepare("DELETE FROM attendance WHERE id = ?");
                $result = $stmt->execute([$attendanceId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Attendance record deleted successfully']);
                } else {
                    throw new Exception('Attendance record not found or could not be deleted');
                }
                
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
}
?>
