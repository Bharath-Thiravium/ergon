<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/TimezoneHelper.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        
        if ($role === 'user') {
            $this->handleUserView();
        } else {
            $this->handleAdminView();
        }
    }
    
    private function handleUserView() {
        $attendance = [];
        $filter = $_GET['filter'] ?? 'today';
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $dateCondition = $this->getDateCondition($filter);
            
            $stmt = $db->prepare("SELECT a.*, u.name as user_name, COALESCE(d.name, 'Not Assigned') as department FROM attendance a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id WHERE a.user_id = ? AND $dateCondition ORDER BY a.check_in DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Times are already in IST, no conversion needed
            
            $stats = $this->calculateUserStats($attendance);
            
        } catch (Exception $e) {
            error_log('Attendance index error: ' . $e->getMessage());
            $stats = ['total_hours' => 0, 'total_minutes' => 0, 'present_days' => 0];
        }
        
        $this->view('attendance/index', [
            'attendance' => $attendance, 
            'stats' => $stats,
            'current_filter' => $filter,
            'active_page' => 'attendance'
        ]);
    }
    
    private function handleAdminView() {
        $employeeAttendance = [];
        $adminAttendance = null;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $db->exec("SET time_zone = '+00:00'");
            $this->ensureAttendanceTable($db);
            
            $filterDate = $_GET['date'] ?? date('Y-m-d');
            $role = $_SESSION['role'] ?? 'admin';
            
            $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user', 'owner')" : "u.role = 'user'";
            
            // Get users with attendance
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    COALESCE(d.name, 'Not Assigned') as department,
                    a.check_in,
                    a.check_out,
                    CASE 
                        WHEN a.location_name = 'On Approved Leave' THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                        ELSE 0
                    END as total_hours
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                WHERE $roleFilter AND u.status = 'active'
                ORDER BY u.role DESC, u.name
            ");
            $stmt->execute([$filterDate]);
            $employeeAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Times are already in IST, no conversion needed
            
            // Get admin's own attendance
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $filterDate]);
            $adminAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Times are already in IST, no conversion needed
            
        } catch (Exception $e) {
            error_log('Attendance error: ' . $e->getMessage());
        }
        
        // Handle AJAX requests
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            $this->handleAjaxResponse($employeeAttendance);
            return;
        }
        
        $viewName = ($_SESSION['role'] === 'owner') ? 'attendance/owner_index' : 'attendance/admin_index';
        $this->view($viewName, [
            'employees' => $employeeAttendance, 
            'admin_attendance' => $adminAttendance,
            'active_page' => 'attendance',
            'filter_date' => $filterDate,
            'user_role' => $_SESSION['role']
        ]);
    }
    
    private function handleAjaxResponse($employees) {
        header('Content-Type: text/html');
        echo "<table class='table'><tbody>";
        
        if (empty($employees)) {
            echo "<tr><td colspan='7' class='text-center text-muted py-4'>No employees found.</td></tr>";
        } else {
            foreach ($employees as $employee) {
                echo "<tr>";
                echo "<td>";
                echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                $bgColor = $employee['role'] === 'admin' ? '#8b5cf6' : ($employee['status'] === 'Present' ? '#22c55e' : '#ef4444');
                $icon = $employee['role'] === 'admin' ? '👔' : strtoupper(substr($employee['name'], 0, 2));
                echo "<div style='width: 32px; height: 32px; border-radius: 50%; background: $bgColor; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: bold;'>$icon</div>";
                echo "<div><div style='font-weight: 500;'>" . htmlspecialchars($employee['name']) . "</div>";
                echo "<div style='font-size: 0.75rem; color: #6b7280;'>" . htmlspecialchars($employee['email']) . "</div></div></div></td>";
                echo "<td>" . htmlspecialchars($employee['department']) . "</td>";
                
                $statusBadge = $employee['status'] === 'Present' ? 'success' : 'danger';
                $statusIcon = $employee['status'] === 'Present' ? '✅' : '❌';
                if ($employee['status'] === 'On Leave') {
                    echo "<td><span class='badge badge--warning'>🏖️ On Leave</span></td>";
                } else {
                    echo "<td><span class='badge badge--$statusBadge'>$statusIcon {$employee['status']}</span></td>";
                }
                
                $checkInTime = $employee['check_in'] ? TimezoneHelper::displayTime($employee['check_in']) : null;
                echo "<td>" . ($checkInTime ? "<span style='color: #059669; font-weight: 500;'>$checkInTime</span>" : '<span style="color: #6b7280;">-</span>') . "</td>";
                
                $checkOutTime = $employee['check_out'] ? TimezoneHelper::displayTime($employee['check_out']) : null;
                if ($checkOutTime) {
                    echo "<td><span style='color: #dc2626; font-weight: 500;'>$checkOutTime</span></td>";
                } elseif ($employee['check_in']) {
                    echo "<td><span style='color: #f59e0b; font-weight: 500;'>Working...</span></td>";
                } else {
                    echo "<td><span style='color: #6b7280;'>-</span></td>";
                }
                
                echo "<td>" . ($employee['total_hours'] > 0 ? "<span style='color: #1f2937; font-weight: 500;'>" . number_format($employee['total_hours'], 2) . "h</span>" : "<span style='color: #6b7280;'>0h</span>") . "</td>";
                echo "<td><div style='display: flex; gap: 0.25rem;'>";
                echo "<button class='btn btn--sm btn--secondary' onclick='viewEmployeeDetails({$employee['id']})' title='View Details'><span>👁️</span></button>";
                if ($employee['status'] === 'Absent') {
                    echo "<button class='btn btn--sm btn--warning' onclick='markManualAttendance({$employee['id']})' title='Manual Entry'><span>✏️</span></button>";
                }
                echo "</div></td></tr>";
            }
        }
        echo "</tbody></table>";
        exit;
    }
    
    public function status() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $onLeave = false;
            try {
                $stmt = $db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                $stmt->execute([$_SESSION['user_id']]);
                $onLeave = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                $onLeave = false;
            }
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'on_leave' => $onLeave,
                'can_clock_in' => !$todayAttendance && !$onLeave,
                'can_clock_out' => $todayAttendance && !$todayAttendance['check_out']
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function manual() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $userId = intval($_POST['user_id']);
                $checkIn = $_POST['check_in'] ?? null;
                $checkOut = $_POST['check_out'] ?? null;
                $date = $_POST['date'] ?? date('Y-m-d');
                
                $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_in = ?, check_out = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $date . ' ' . $checkIn,
                        $checkOut ? $date . ' ' . $checkOut : null,
                        $existing['id']
                    ]);
                } else {
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at) VALUES (?, ?, ?, 'present', 'Manual Entry', NOW())");
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleClockAction();
        } else {
            $this->showClockPage();
        }
    }
    
    private function handleClockAction() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $db->exec("SET time_zone = '+00:00'");
            $this->ensureAttendanceTable($db);
            
            $type = $_POST['type'] ?? '';
            $userId = $_SESSION['user_id'];
            
            header('Content-Type: application/json');
            
            if ($type === 'in') {
                $this->handleClockIn($db, $userId);
            } elseif ($type === 'out') {
                $this->handleClockOut($db, $userId);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            exit;
            
        } catch (Exception $e) {
            error_log('Attendance clock error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    private function handleClockIn($db, $userId) {
        // Check if already clocked in today
        $currentDate = TimezoneHelper::getCurrentDate();
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ? AND (check_out IS NULL OR check_out = '')");
        $stmt->execute([$userId, $currentDate]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Already clocked in today']);
            return;
        }
        
        // Check location restriction
        if (!$this->isWithinAllowedLocation($db)) {
            echo json_encode(['success' => false, 'error' => 'Please move within the allowed area to continue.']);
            return;
        }
        
        // Store in IST
        $currentTime = TimezoneHelper::nowIst();
        
        $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, created_at) VALUES (?, ?, ?)");
        $result = $stmt->execute([$userId, $currentTime, $currentTime]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to clock in']);
        }
    }
    
    private function handleClockOut($db, $userId) {
        $currentTime = TimezoneHelper::nowIst();
        $currentDate = TimezoneHelper::getCurrentDate();
        
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL");
        $stmt->execute([$userId, $currentDate]);
        $attendance = $stmt->fetch();
        
        if (!$attendance) {
            echo json_encode(['success' => false, 'error' => 'No clock in record found for today']);
            return;
        }
        
        // Check location restriction
        if (!$this->isWithinAllowedLocation($db)) {
            echo json_encode(['success' => false, 'error' => 'Please move within the allowed area to continue.']);
            return;
        }
        
        $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
        $result = $stmt->execute([$currentTime, $attendance['id']]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to clock out']);
        }
    }
    
    private function showClockPage() {
        $todayAttendance = null;
        $onLeave = false;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureAttendanceTable($db);
            
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user is on leave
            try {
                $stmt = $db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                $stmt->execute([$_SESSION['user_id']]);
                $onLeave = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                $onLeave = false;
            }
            
        } catch (Exception $e) {
            error_log('Today attendance fetch error: ' . $e->getMessage());
        }
        
        // Prepare attendance status for JavaScript
        $attendanceStatus = [
            'has_clocked_in' => $todayAttendance ? true : false,
            'has_clocked_out' => $todayAttendance && $todayAttendance['check_out'] ? true : false,
            'on_leave' => $onLeave,
            'is_completed' => $todayAttendance && $todayAttendance['check_out'] ? true : false
        ];
        
        $this->view('attendance/clock', [
            'today_attendance' => $todayAttendance, 
            'on_leave' => $onLeave, 
            'attendance_status' => $attendanceStatus,
            'active_page' => 'attendance'
        ]);
    }
    
    private function ensureAttendanceTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                check_in DATETIME NOT NULL,
                check_out DATETIME NULL,
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                location_name VARCHAR(255) DEFAULT 'Office',
                status VARCHAR(20) DEFAULT 'present',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_check_in_date (check_in)
            )");
            
            $db->exec("UPDATE attendance SET check_out = NULL WHERE check_out = '' OR check_out = '0000-00-00 00:00:00'");
            
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
    
    private function isWithinAllowedLocation($db) {
        // Get user's current location from POST data
        $userLat = floatval($_POST['latitude'] ?? 0);
        $userLng = floatval($_POST['longitude'] ?? 0);
        
        if ($userLat == 0 || $userLng == 0) {
            error_log("Location validation failed: No location provided (lat: $userLat, lng: $userLng)");
            return false; // No location provided
        }
        
        // Ensure settings table exists
        $this->ensureSettingsTable($db);
        
        // Get office location and radius from settings
        $stmt = $db->prepare("SELECT base_location_lat, base_location_lng, attendance_radius FROM settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings || $settings['base_location_lat'] == 0 || $settings['base_location_lng'] == 0) {
            error_log("Location validation: No office location set, allowing attendance");
            return true; // No location restrictions set
        }
        
        $officeLat = floatval($settings['base_location_lat']);
        $officeLng = floatval($settings['base_location_lng']);
        $allowedRadius = intval($settings['attendance_radius']);
        
        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($userLat, $userLng, $officeLat, $officeLng);
        
        error_log("Location validation: User($userLat,$userLng) Office($officeLat,$officeLng) Distance: {$distance}m Radius: {$allowedRadius}m Result: " . ($distance <= $allowedRadius ? 'ALLOWED' : 'BLOCKED'));
        
        return $distance <= $allowedRadius;
    }
    
    private function ensureSettingsTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) DEFAULT 'ERGON Company',
                base_location_lat DECIMAL(10,8) DEFAULT 0,
                base_location_lng DECIMAL(11,8) DEFAULT 0,
                attendance_radius INT DEFAULT 5,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Insert default settings if none exist
            $stmt = $db->query("SELECT COUNT(*) as count FROM settings");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] == 0) {
                $db->exec("INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius) VALUES ('ERGON Company', 0, 0, 5)");
            }
        } catch (Exception $e) {
            error_log('ensureSettingsTable error: ' . $e->getMessage());
        }
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
            if ($record['check_in'] && $record['check_out']) {
                $minutes = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 60;
                $totalMinutes += $minutes;
                $presentDays++;
            } elseif ($record['check_in']) {
                $presentDays++;
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
}
?>