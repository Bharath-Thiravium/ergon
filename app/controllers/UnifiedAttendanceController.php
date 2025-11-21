<?php
require_once __DIR__ . '/../core/Controller.php';

class UnifiedAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $this->db = Database::connect();
            $this->ensureAttendanceTable();
        } catch (Exception $e) {
            error_log('UnifiedAttendanceController constructor error: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function index() {
        try {
            $this->requireAuth();
            
            $role = $_SESSION['role'] ?? 'user';
            $userId = $_SESSION['user_id'];
            
            // Get date filter
            $selectedDate = $_GET['date'] ?? date('Y-m-d');
            $filter = $_GET['filter'] ?? 'today';
            
            // Get attendance records
            $attendance = $this->getAllAttendanceByDate($selectedDate, $role, $userId);
            $stats = $this->calculateUserStats($attendance);
            
            // For admin, get their own attendance separately
            $adminAttendance = null;
            if ($role === 'admin') {
                $adminAttendance = $this->getAdminOwnAttendance($userId, $selectedDate);
            }
            
            $this->view('attendance/index', [
                'attendance' => $attendance,
                'admin_attendance' => $adminAttendance,
                'stats' => $stats,
                'current_filter' => $filter,
                'selected_date' => $selectedDate,
                'user_role' => $role,
                'active_page' => 'attendance',
                'is_grouped' => $role === 'owner' && is_array($attendance) && isset($attendance['admin'])
            ]);
        } catch (Exception $e) {
            error_log('Attendance index error: ' . $e->getMessage());
            http_response_code(500);
            echo "<h1>Attendance Error</h1><p>Unable to load attendance data. Please check the database connection.</p>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<a href='/ergon/dashboard'>Return to Dashboard</a>";
        }
    }
    
    public function clock() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $type = $_POST['type'] ?? '';
                $latitude = floatval($_POST['latitude'] ?? 0);
                $longitude = floatval($_POST['longitude'] ?? 0);
                $userId = $_SESSION['user_id'];
                
                if ($type === 'in') {
                    echo json_encode($this->clockIn($userId, $latitude, $longitude));
                } elseif ($type === 'out') {
                    echo json_encode($this->clockOut($userId));
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
                
            } catch (Exception $e) {
                error_log('Attendance clock error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Server error occurred']);
            }
            exit;
        }
        
        // GET request - show clock page
        $todayAttendance = $this->getTodayAttendance($_SESSION['user_id']);
        $onLeave = $this->checkIfOnLeave($_SESSION['user_id']);
        
        // Prepare attendance status for smart button
        $attendanceStatus = [
            'has_clocked_in' => $todayAttendance ? true : false,
            'has_clocked_out' => $todayAttendance && $todayAttendance['check_out'] ? true : false,
            'clock_in_time' => $todayAttendance ? $todayAttendance['check_in'] : null,
            'clock_out_time' => $todayAttendance ? $todayAttendance['check_out'] : null,
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
    
    public function status() {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        try {
            $todayAttendance = $this->getTodayAttendance($_SESSION['user_id']);
            $onLeave = $this->checkIfOnLeave($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'on_leave' => $onLeave,
                'can_clock_in' => !$onLeave && (!$todayAttendance || $todayAttendance['check_out']),
                'can_clock_out' => !$onLeave && $todayAttendance && !$todayAttendance['check_out']
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    private function clockIn($userId, $latitude, $longitude) {
        try {
            // Check if already has attendance today
            $existing = $this->getTodayAttendance($userId);
            if ($existing) {
                if ($existing['check_out']) {
                    return ['success' => false, 'error' => 'Already completed attendance for today'];
                } else {
                    return ['success' => false, 'error' => 'Already clocked in today'];
                }
            }
            
            // Check if on approved leave
            if ($this->checkIfOnLeave($userId)) {
                return ['success' => false, 'error' => 'You are on approved leave today'];
            }
            
            // Insert attendance record
            $stmt = $this->db->prepare("
                INSERT INTO attendance (user_id, check_in, created_at) 
                VALUES (?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Clocked in successfully',
                    'time' => date('H:i:s')
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to clock in'];
            
        } catch (Exception $e) {
            error_log('Clock in error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    private function clockOut($userId) {
        try {
            // Find today's attendance record
            $stmt = $this->db->prepare("
                SELECT id, check_in FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL
            ");
            $stmt->execute([$userId]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attendance) {
                return ['success' => false, 'error' => 'No clock in record found for today'];
            }
            
            // Update attendance record
            $stmt = $this->db->prepare("
                UPDATE attendance 
                SET check_out = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$attendance['id']]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Clocked out successfully',
                    'time' => date('H:i:s')
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to clock out'];
            
        } catch (Exception $e) {
            error_log('Clock out error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    private function getAllAttendanceByDate($selectedDate, $role, $userId) {
        try {
            // Role-based filtering
            if ($role === "user") {
                $userCondition = "AND u.id = $userId";
            } elseif ($role === "admin") {
                $userCondition = "AND u.role IN ('user', 'admin') AND u.id != $userId";
            } else {
                $userCondition = "AND u.role IN ('user', 'admin') AND (u.status != 'removed' OR u.status IS NULL)";
            }
            
            // Get all users with their attendance and leave status for selected date
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.name, 
                    u.email, 
                    u.role,
                    a.id as attendance_id,
                    a.check_in,
                    a.check_out,
                    CASE 
                        WHEN l.id IS NOT NULL THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    CASE 
                        WHEN l.id IS NOT NULL THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                                   TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                        WHEN a.check_in IS NOT NULL THEN 'Working...'
                        ELSE '0h 0m'
                    END as working_hours,
                    CASE 
                        WHEN l.id IS NOT NULL THEN '00:00'
                        ELSE COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00')
                    END as check_in_time,
                    CASE 
                        WHEN l.id IS NOT NULL THEN '00:00'
                        ELSE COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00')
                    END as check_out_time
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                LEFT JOIN leaves l ON u.id = l.user_id AND l.status = 'approved' 
                    AND ? BETWEEN DATE(l.start_date) AND DATE(l.end_date)
                WHERE (u.status = 'active' OR u.status IS NULL) $userCondition
                ORDER BY 
                    CASE 
                        WHEN u.role = 'admin' THEN 1
                        WHEN u.role = 'user' THEN 2
                        ELSE 3
                    END, u.name
            ");
            $stmt->execute([$selectedDate, $selectedDate]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by role for Owner panel
            if ($role === 'owner') {
                $grouped = ['admin' => [], 'user' => []];
                foreach ($records as $record) {
                    $userRole = $record['role'] === 'admin' ? 'admin' : 'user';
                    $grouped[$userRole][] = $record;
                }
                return $grouped;
            }
            
            return $records;
        } catch (Exception $e) {
            error_log('getAllAttendanceByDate error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getTodayAttendance($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM attendance 
                WHERE user_id = ? AND DATE(check_in) = CURDATE()
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getTodayAttendance error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function checkIfOnLeave($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM leaves 
                WHERE user_id = ? AND status = 'approved' 
                AND CURDATE() BETWEEN DATE(start_date) AND DATE(end_date)
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch() ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getAdminOwnAttendance($userId, $selectedDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.name as user_name, 
                    u.email, 
                    u.role as user_role,
                    a.id as attendance_id,
                    a.check_in,
                    a.check_out,
                    CASE 
                        WHEN l.id IS NOT NULL THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    CASE 
                        WHEN l.id IS NOT NULL THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                                   TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                        WHEN a.check_in IS NOT NULL THEN 'Working...'
                        ELSE '0h 0m'
                    END as working_hours,
                    CASE 
                        WHEN l.id IS NOT NULL THEN '00:00'
                        ELSE COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00')
                    END as check_in_time,
                    CASE 
                        WHEN l.id IS NOT NULL THEN '00:00'
                        ELSE COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00')
                    END as check_out_time
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                LEFT JOIN leaves l ON u.id = l.user_id AND l.status = 'approved' 
                    AND ? BETWEEN DATE(l.start_date) AND DATE(l.end_date)
                WHERE u.id = ?
            ");
            $stmt->execute([$selectedDate, $selectedDate, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getAdminOwnAttendance error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function calculateUserStats($attendance) {
        $totalMinutes = 0;
        $presentDays = 0;
        
        // Handle grouped attendance for owner
        $records = [];
        if (is_array($attendance) && isset($attendance['admin'])) {
            $records = array_merge($attendance['admin'], $attendance['user']);
        } else {
            $records = $attendance;
        }
        
        foreach ($records as $record) {
            if ($record['check_in'] && $record['check_out']) {
                $minutes = (float)((strtotime($record['check_out']) - strtotime($record['check_in'])) / 60.0);
                $totalMinutes += $minutes;
                $presentDays++;
            } elseif ($record['check_in']) {
                $presentDays++;
            }
        }
        
        $totalHours = (int)floor($totalMinutes / 60);
        $remainingMinutes = (int)($totalMinutes - ($totalHours * 60));
        
        return [
            'total_hours' => $totalHours,
            'total_minutes' => $remainingMinutes,
            'present_days' => $presentDays
        ];
    }
    
    private function ensureAttendanceTable() {
        try {
            // Check if users table exists first
            $stmt = $this->db->query("SHOW TABLES LIKE 'users'");
            if (!$stmt->fetch()) {
                throw new Exception('Users table does not exist. Please run database migration first.');
            }
            
            // Check if attendance table exists with proper structure
            $stmt = $this->db->query("SHOW TABLES LIKE 'attendance'");
            if (!$stmt->fetch()) {
                // Table doesn't exist, create it
                $this->db->exec("
                    CREATE TABLE attendance (
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
                    )
                ");
            }
        } catch (Exception $e) {
            error_log('ensureAttendanceTable error: ' . $e->getMessage());
            throw new Exception('Failed to ensure attendance table: ' . $e->getMessage());
        }
    }
}
?>