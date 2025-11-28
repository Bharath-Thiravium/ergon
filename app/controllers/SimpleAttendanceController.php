<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/TimezoneHelper.php';
require_once __DIR__ . '/../helpers/LocationHelper.php';

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
        
        // Query to get all users with their attendance data for the selected date
        $roleFilter = '';
        if ($role === 'user') {
            $roleFilter = "AND u.id = $userId";
        } elseif ($role === 'admin') {
            $roleFilter = "AND u.role IN ('user')";
        } else {
            $roleFilter = "AND u.role IN ('admin', 'user')";
        }
        
        if ($selectedDate === TimezoneHelper::getCurrentDate()) {
            // For current date, show all users including those without attendance records
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
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00') as check_in_time,
                    COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00') as check_out_time,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                                   TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                        ELSE '0h 0m'
                    END as working_hours
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.created_at) = ?
                WHERE u.status = 'active' {$roleFilter}
                ORDER BY u.role DESC, u.name
            ");
        } else {
            // For past dates, only show users who have attendance records
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
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00') as check_in_time,
                    COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00') as check_out_time,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                                   TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                        ELSE '0h 0m'
                    END as working_hours
                FROM users u
                INNER JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                WHERE u.status = 'active' {$roleFilter}
                ORDER BY u.role DESC, u.name
            ");
        }
        $stmt->execute([$selectedDate]);
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
        
        // Calculate stats for user view
        $stats = ['total_hours' => 0, 'total_minutes' => 0, 'present_days' => 0];
        if ($role === 'user' && !empty($records)) {
            $stats = $this->calculateUserStats($records);
        }
        
        $this->view('attendance/index', [
            'attendance' => $attendance,
            'stats' => $stats,
            'current_filter' => 'today',
            'selected_date' => $selectedDate,
            'user_role' => $role,
            'active_page' => 'attendance',
            'is_grouped' => $isGrouped
        ]);
    }
    
    // Additional methods from AttendanceController
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
            header('Content-Type: application/json');
            
            try {
                $userId = intval($_POST['user_id']);
                $checkIn = $_POST['check_in'] ?? null;
                $checkOut = $_POST['check_out'] ?? null;
                $date = $_POST['date'] ?? date('Y-m-d');
                
                // Check for existing record - use created_at for safer date comparison
                $stmt = $this->db->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND DATE(created_at) = ?");
                $stmt->execute([$userId, $date]);
                $existing = $stmt->fetch();
                
                if ($checkIn && !$existing) {
                    // Clock in - create new record
                    $currentTime = TimezoneHelper::nowIst();
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name, created_at) VALUES (?, ?, 'present', 'Manual Entry', ?)");
                    $stmt->execute([$userId, $currentTime, $currentTime]);
                    echo json_encode(['success' => true, 'message' => 'User clocked in successfully']);
                } elseif ($checkOut && $existing && !$existing['check_out']) {
                    // Clock out - update existing record
                    $currentTime = TimezoneHelper::nowIst();
                    $stmt = $this->db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                    $stmt->execute([$currentTime, $existing['id']]);
                    echo json_encode(['success' => true, 'message' => 'User clocked out successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid operation or user already has attendance record']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    public function delete() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $attendanceId = intval($_POST['attendance_id']);
                
                $stmt = $this->db->prepare("DELETE FROM attendance WHERE id = ?");
                $result = $stmt->execute([$attendanceId]);
                
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Record deleted successfully' : 'Failed to delete record'
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    public function clock() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleClockAction();
        } else {
            $this->showClockPage();
        }
    }
    
    private function handleClockAction() {
        header('Content-Type: application/json');
        
        try {
            $type = $_POST['type'] ?? '';
            $userId = $_SESSION['user_id'];
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            
            // Validate location if coordinates provided
            if ($latitude && $longitude) {
                $officeSettings = LocationHelper::getOfficeSettings($this->db);
                $locationCheck = LocationHelper::isWithinAttendanceRadius($latitude, $longitude, $officeSettings);
                
                if (!$locationCheck['allowed']) {
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Please move within the allowed area to continue.',
                        'distance' => $locationCheck['distance'],
                        'allowed_radius' => $locationCheck['allowed_radius']
                    ]);
                    exit;
                }
            }
            
            if ($type === 'in') {
                $this->handleClockIn($userId, $latitude, $longitude);
            } elseif ($type === 'out') {
                $this->handleClockOut($userId, $latitude, $longitude);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            
        } catch (Exception $e) {
            error_log('Attendance clock error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    private function handleClockIn($userId, $latitude = null, $longitude = null) {
        $currentDate = TimezoneHelper::getCurrentDate();
        $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$userId, $currentDate]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Already clocked in today']);
            return;
        }
        
        $currentTime = TimezoneHelper::nowIst();
        
        $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, latitude, longitude, created_at) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$userId, $currentTime, $latitude, $longitude, $currentTime]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Clocked in successfully' : 'Failed to clock in'
        ]);
    }
    
    private function handleClockOut($userId, $latitude = null, $longitude = null) {
        $currentTime = TimezoneHelper::nowIst();
        $currentDate = TimezoneHelper::getCurrentDate();
        
        $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(created_at) = ? AND check_out IS NULL");
        $stmt->execute([$userId, $currentDate]);
        $attendance = $stmt->fetch();
        
        if (!$attendance) {
            echo json_encode(['success' => false, 'error' => 'No clock in record found for today']);
            return;
        }
        
        // Update with location data if provided
        if ($latitude && $longitude) {
            $stmt = $this->db->prepare("UPDATE attendance SET check_out = ?, latitude = COALESCE(latitude, ?), longitude = COALESCE(longitude, ?) WHERE id = ?");
            $result = $stmt->execute([$currentTime, $latitude, $longitude, $attendance['id']]);
        } else {
            $stmt = $this->db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
            $result = $stmt->execute([$currentTime, $attendance['id']]);
        }
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Clocked out successfully' : 'Failed to clock out'
        ]);
    }
    
    private function showClockPage() {
        $todayAttendance = null;
        $onLeave = false;
        
        try {
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check leave status
            try {
                $stmt = $this->db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                $stmt->execute([$_SESSION['user_id']]);
                $onLeave = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                $onLeave = false;
            }
            
        } catch (Exception $e) {
            error_log('Today attendance fetch error: ' . $e->getMessage());
        }
        
        // Prepare attendance status for the view
        $attendanceStatus = [
            'has_clocked_in' => $todayAttendance && $todayAttendance['check_in'] ? true : false,
            'has_clocked_out' => $todayAttendance && $todayAttendance['check_out'] ? true : false,
            'on_leave' => $onLeave,
            'is_completed' => $todayAttendance && $todayAttendance['check_in'] && $todayAttendance['check_out'] ? true : false,
            'clock_in_time' => $todayAttendance && $todayAttendance['check_in'] ? $todayAttendance['check_in'] : null,
            'clock_out_time' => $todayAttendance && $todayAttendance['check_out'] ? $todayAttendance['check_out'] : null
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
    
    // Additional view methods for different user types
    private function handleUserView() {
        $attendance = [];
        $filter = $_GET['filter'] ?? 'today';
        
        try {
            $dateCondition = $this->getDateCondition($filter);
            
            $stmt = $this->db->prepare("SELECT a.*, u.name as user_name, COALESCE(d.name, 'Not Assigned') as department FROM attendance a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id WHERE a.user_id = ? AND $dateCondition ORDER BY a.check_in DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            $filterDate = $_GET['date'] ?? date('Y-m-d');
            $role = $_SESSION['role'] ?? 'admin';
            
            $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user', 'owner')" : "u.role = 'user'";
            
            $stmt = $this->db->prepare("
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
            
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $filterDate]);
            $adminAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Attendance error: ' . $e->getMessage());
        }
        
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
}
?>