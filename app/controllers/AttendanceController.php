<?php
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        
        if ($role === 'user') {
            // User view - show only their attendance
            $attendance = [];
            $filter = $_GET['filter'] ?? 'today';
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Calculate date range based on filter
                $dateCondition = $this->getDateCondition($filter);
                
                $stmt = $db->prepare("SELECT a.*, u.name as user_name, COALESCE(d.name, 'Not Assigned') as department FROM attendance a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id WHERE a.user_id = ? AND $dateCondition ORDER BY a.check_in DESC");
                $stmt->execute([$_SESSION['user_id']]);
                $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate stats for the filtered period
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
        } else {
            // Admin/Owner view - show employees or all users based on role
            $employeeAttendance = [];
            $adminAttendance = null;
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $this->ensureAttendanceTable($db);
                
                // Get date filter from query parameter
                $filterDate = $_GET['date'] ?? date('Y-m-d');
                
                // Consistent role filtering: Owner sees all users, Admin sees users only
                if ($role === 'owner') {
                    $roleFilter = "u.role IN ('admin', 'user', 'owner')";
                } else {
                    $roleFilter = "u.role = 'user'";
                }
                
                // Get users with attendance status for selected date
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
                
                // Debug output
                error_log("AttendanceController Debug:");
                error_log("- Role: $role");
                error_log("- Role Filter: $roleFilter");
                error_log("- Filter Date: $filterDate");
                error_log("- Query Result Count: " . count($employeeAttendance));
                error_log("- Current User ID: " . ($_SESSION['user_id'] ?? 'not set'));
                
                if (empty($employeeAttendance)) {
                    // Check if users exist at all
                    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
                    $totalUsers = $stmt->fetch()['count'];
                    error_log("- Total users in database: $totalUsers");
                    
                    $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                    $roleStats = $stmt->fetchAll();
                    foreach ($roleStats as $stat) {
                        error_log("- Role {$stat['role']}: {$stat['count']} users");
                    }
                }
                
                // Get admin's own attendance for today
                $adminAttendance = null;
                $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$_SESSION['user_id'], $filterDate]);
                $adminAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                error_log('Attendance error: ' . $e->getMessage());
            }
            
            // Handle AJAX requests for real-time updates
            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                // Return only the table body for AJAX refresh
                header('Content-Type: text/html');
                echo "<table class='table'><tbody>";
                
                if (empty($employeeAttendance)) {
                    echo "<tr><td colspan='7' class='text-center text-muted py-4'>No employees found.</td></tr>";
                } else {
                    foreach ($employeeAttendance as $employee) {
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
                        echo "<td>" . ($employee['check_in'] ? date('H:i', strtotime($employee['check_in'])) : '<span style="color: #6b7280;">-</span>') . "</td>";
                        if ($employee['check_out']) {
                            echo "<td><span style='color: #dc2626; font-weight: 500;'>" . date('H:i', strtotime($employee['check_out'])) . "</span></td>";
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
            
            $viewName = ($role === 'owner') ? 'attendance/owner_index' : 'attendance/admin_index';
            $this->view($viewName, [
                'employees' => $employeeAttendance, 
                'admin_attendance' => $adminAttendance,
                'active_page' => 'attendance',
                'filter_date' => $filterDate,
                'user_role' => $role
            ]);
        }
    }
    
    public function clock() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $this->ensureAttendanceTable($db);
                
                $type = $_POST['type'] ?? '';
                $latitude = floatval($_POST['latitude'] ?? 0);
                $longitude = floatval($_POST['longitude'] ?? 0);
                $userId = $_SESSION['user_id'];
                
                header('Content-Type: application/json');
                
                if ($type === 'in') {
                    // Skip location validation for now
                    // if (!$this->validateOfficeLocation($latitude, $longitude, $db)) {
                    //     $distance = $this->calculateDistance($latitude, $longitude, $db);
                    //     echo json_encode(['success' => false, 'error' => "You are {$distance}m away from office. Please move closer."]);
                    //     exit;
                    // }
                    
                    // Check if user is on approved leave today
                    try {
                        $stmt = $db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                        $stmt->execute([$userId]);
                        
                        if ($stmt->fetch()) {
                            echo json_encode(['success' => false, 'error' => 'You are on approved leave today']);
                            exit;
                        }
                    } catch (Exception $e) {
                        // If leaves table doesn't exist, skip leave check
                        error_log('Leave check error (table may not exist): ' . $e->getMessage());
                    }
                    
                    // Check if already clocked in today - handle missing columns gracefully
                    try {
                        // Check what columns exist in attendance table
                        $stmt = $db->query("SHOW COLUMNS FROM attendance");
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (in_array('check_in', $columns)) {
                            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND (check_out IS NULL OR check_out = '')");
                        } else {
                            // Fallback to created_at if check_in doesn't exist
                            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(created_at) = CURDATE()");
                        }
                        $stmt->execute([$userId]);
                        
                        if ($stmt->fetch()) {
                            echo json_encode(['success' => false, 'error' => 'Already clocked in today']);
                            exit;
                        }
                    } catch (Exception $e) {
                        error_log('Clock in check error: ' . $e->getMessage());
                        // Continue with clock in if check fails
                    }
                    
                    // Get user's timezone preference
                    $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $userPrefs = $stmt->fetch();
                    $timezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
                    
                    date_default_timezone_set($timezone);
                    $currentTime = date('Y-m-d H:i:s');
                    
                    // Clock in - handle both column name variations
                    $stmt = $db->query("SHOW COLUMNS FROM attendance");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('check_in', $columns)) {
                        $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, created_at) VALUES (?, ?, ?)");
                        $stmt->execute([$userId, $currentTime, $currentTime]);
                    } elseif (in_array('clock_in', $columns)) {
                        $stmt = $db->prepare("INSERT INTO attendance (user_id, clock_in, date, created_at) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, date('H:i:s'), date('Y-m-d'), $currentTime]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO attendance (user_id, created_at) VALUES (?, ?)");
                        $stmt->execute([$userId, $currentTime]);
                    }
                    $result = true; // Set result since we're handling execute manually
                    // Remove this line since we're handling execute manually above
                    
                    if ($result) {
                        // Check if late arrival (after 9:30 AM) and notify owners
                        $currentTime = date('H:i:s');
                        if ($currentTime > '09:30:00') {
                            require_once __DIR__ . '/../helpers/NotificationHelper.php';
                            $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user) {
                                NotificationHelper::notifyOwners(
                                    $userId,
                                    'attendance',
                                    'late_arrival',
                                    "{$user['name']} arrived late at " . date('H:i'),
                                    $db->lastInsertId()
                                );
                            }
                        }
                        echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to clock in']);
                    }
                    
                } elseif ($type === 'out') {
                    // Set timezone to IST for correct time recording
                    date_default_timezone_set('Asia/Kolkata');
                    $currentDate = date('Y-m-d');
                    $currentTime = date('Y-m-d H:i:s');
                    
                    // Find today's attendance record
                    $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL");
                    $stmt->execute([$userId, $currentDate]);
                    $attendance = $stmt->fetch();
                    
                    if (!$attendance) {
                        echo json_encode(['success' => false, 'error' => 'No clock in record found for today']);
                        exit;
                    }
                    
                    $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                    $result = $stmt->execute([$currentTime, $attendance['id']]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to clock out']);
                    }
                    
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
                exit;
                
            } catch (Exception $e) {
                error_log('Attendance clock error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
                exit;
            }
        }
        
        // GET request - show clock page
        $todayAttendance = null;
        $onLeave = false;
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureAttendanceTable($db);
            
            // Set timezone to IST for correct date comparison
            date_default_timezone_set('Asia/Kolkata');
            $currentDate = date('Y-m-d');
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user is on approved leave today
            try {
                $stmt = $db->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date");
                $stmt->execute([$_SESSION['user_id']]);
                $onLeave = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                // If leaves table doesn't exist, assume not on leave
                error_log('Leave check error (table may not exist): ' . $e->getMessage());
                $onLeave = false;
            }
        } catch (Exception $e) {
            error_log('Today attendance fetch error: ' . $e->getMessage());
        }
        
        $this->view('attendance/clock', ['today_attendance' => $todayAttendance, 'on_leave' => $onLeave, 'active_page' => 'attendance']);
    }
    
    public function status() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Set timezone to IST for correct date comparison
            date_default_timezone_set('Asia/Kolkata');
            $currentDate = date('Y-m-d');
            
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
            
            // Clean any empty string values that cause DATETIME errors
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
    
    public function exportAttendance() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/attendance?error=Access denied');
            exit;
        }
        
        // Check if this is a single user report
        if (isset($_GET['user_id'])) {
            return $this->exportUserReport();
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get date range from query parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
            
            $stmt = $db->prepare("
                SELECT 
                    u.name as employee_name,
                    u.email,
                    COALESCE(d.name, 'Not Assigned') as department,
                    DATE(a.check_in) as date,
                    TIME(a.check_in) as check_in_time,
                    TIME(a.check_out) as check_out_time,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                        ELSE 0
                    END as total_hours,
                    CASE 
                        WHEN a.location_name = 'On Approved Leave' THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) BETWEEN ? AND ?
                WHERE u.role IN ('user', 'admin')
                ORDER BY u.name, DATE(a.check_in)
            ");
            $stmt->execute([$startDate, $endDate]);
            $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_export_' . $startDate . '_to_' . $endDate . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Employee Name', 'Email', 'Department', 'Date', 'Check In', 'Check Out', 'Total Hours', 'Status']);
            
            foreach ($attendanceData as $record) {
                fputcsv($output, [
                    $record['employee_name'],
                    $record['email'],
                    $record['department'],
                    $record['date'] ?? 'N/A',
                    $record['check_in_time'] ?? 'N/A',
                    $record['check_out_time'] ?? 'N/A',
                    $record['total_hours'],
                    $record['status']
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log('Attendance export error: ' . $e->getMessage());
            header('Location: /ergon/attendance?error=Export failed');
            exit;
        }
    }
    
    private function exportUserReport() {
        $userId = intval($_GET['user_id']);
        $fromDate = $_GET['from'] ?? date('Y-m-01');
        $toDate = $_GET['to'] ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get user details
            $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                header('Location: /ergon/attendance?error=User not found');
                exit;
            }
            
            // Get attendance data
            $stmt = $db->prepare("
                SELECT 
                    DATE(check_in) as date,
                    TIME(check_in) as check_in_time,
                    TIME(check_out) as check_out_time,
                    CASE 
                        WHEN check_in IS NOT NULL AND check_out IS NOT NULL THEN 
                            ROUND(TIMESTAMPDIFF(MINUTE, check_in, check_out) / 60.0, 2)
                        ELSE 0
                    END as total_hours,
                    status
                FROM attendance 
                WHERE user_id = ? AND DATE(check_in) BETWEEN ? AND ?
                ORDER BY DATE(check_in)
            ");
            $stmt->execute([$userId, $fromDate, $toDate]);
            $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $user['name'] . '_attendance_' . $fromDate . '_to_' . $toDate . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Employee: ' . $user['name']]);
            fputcsv($output, ['Email: ' . $user['email']]);
            fputcsv($output, ['Period: ' . $fromDate . ' to ' . $toDate]);
            fputcsv($output, []);
            fputcsv($output, ['Date', 'Check In', 'Check Out', 'Total Hours', 'Status']);
            
            foreach ($attendanceData as $record) {
                fputcsv($output, [
                    $record['date'],
                    $record['check_in_time'] ?? 'N/A',
                    $record['check_out_time'] ?? 'N/A',
                    $record['total_hours'],
                    $record['status']
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log('User report export error: ' . $e->getMessage());
            header('Location: /ergon/attendance?error=Export failed');
            exit;
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
    
    private function validateOfficeLocation($userLat, $userLng, $db) {
        try {
            // Skip validation if coordinates are invalid
            if (!$userLat || !$userLng || $userLat == 0 || $userLng == 0) {
                return true; // Allow if no valid GPS
            }
            
            // Get office location from settings
            $stmt = $db->query("SELECT base_location_lat, base_location_lng, attendance_radius FROM settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings || !$settings['base_location_lat'] || !$settings['base_location_lng']) {
                return true; // Allow if no office location set
            }
            
            $officeLat = floatval($settings['base_location_lat']);
            $officeLng = floatval($settings['base_location_lng']);
            $allowedRadius = max(50, intval($settings['attendance_radius'] ?? 200)); // Minimum 50m
            
            // Skip validation if office coordinates are default/invalid
            if ($officeLat == 0 || $officeLng == 0) {
                return true;
            }
            
            $distance = $this->haversineDistance($userLat, $userLng, $officeLat, $officeLng);
            
            return $distance <= $allowedRadius;
        } catch (Exception $e) {
            error_log('Location validation error: ' . $e->getMessage());
            return true; // Allow if validation fails
        }
    }
    
    private function calculateDistance($userLat, $userLng, $db) {
        try {
            $stmt = $db->query("SELECT base_location_lat, base_location_lng FROM settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings || !$settings['base_location_lat'] || !$settings['base_location_lng']) {
                return 0;
            }
            
            $officeLat = floatval($settings['base_location_lat']);
            $officeLng = floatval($settings['base_location_lng']);
            
            return round($this->haversineDistance($userLat, $userLng, $officeLat, $officeLng));
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function haversineDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    public function history($employeeId = null) {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
        
        if (!$employeeId) {
            header('Location: /ergon/attendance?error=Employee ID required');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get employee details
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                header('Location: /ergon/attendance?error=Employee not found');
                exit;
            }
            
            // Get period filter
            $period = intval($_GET['period'] ?? 30);
            
            // Get attendance history
            $stmt = $db->prepare("
                SELECT 
                    check_in,
                    check_out,
                    status,
                    location_name,
                    created_at
                FROM attendance 
                WHERE user_id = ? 
                AND check_in >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY check_in DESC
            ");
            $stmt->execute([$employeeId, $period]);
            $attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('attendance/history', [
                'employee' => $employee,
                'employee_id' => $employeeId,
                'attendance_history' => $attendanceHistory,
                'period' => $period,
                'active_page' => 'attendance'
            ]);
            
        } catch (Exception $e) {
            error_log('Attendance history error: ' . $e->getMessage());
            header('Location: /ergon/attendance?error=Failed to load attendance history');
            exit;
        }
    }
}
?>
