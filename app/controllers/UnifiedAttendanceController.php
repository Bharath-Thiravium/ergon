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
            
            if ($role === 'user') {
                // User view - show only their attendance
                $filter = $_GET['filter'] ?? 'today';
                $attendance = $this->getUserAttendance($userId, $filter);
                $stats = $this->calculateUserStats($attendance);
                
                $this->view('attendance/index', [
                    'attendance' => $attendance,
                    'stats' => $stats,
                    'current_filter' => $filter,
                    'active_page' => 'attendance'
                ]);
            } else {
                // Admin/Owner view
                $filterDate = $_GET['date'] ?? date('Y-m-d');
                $employeeAttendance = $this->getEmployeeAttendance($role, $filterDate, $userId);
                $adminAttendance = $this->getTodayAttendance($userId);
                
                // Handle AJAX requests
                if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                    $this->renderAttendanceTable($employeeAttendance);
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
        
        $this->view('attendance/clock', [
            'today_attendance' => $todayAttendance,
            'on_leave' => $onLeave,
            'active_page' => 'attendance'
        ]);
    }
    
    public function status() {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        try {
            $todayAttendance = $this->getTodayAttendance($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'can_clock_in' => !$todayAttendance || $todayAttendance['check_out'],
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
                
                // Check if record exists
                $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing record
                    $stmt = $this->db->prepare("UPDATE attendance SET check_in = ?, check_out = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $date . ' ' . $checkIn,
                        $checkOut ? $date . ' ' . $checkOut : null,
                        $existing['id']
                    ]);
                } else {
                    // Create new record
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at) VALUES (?, ?, ?, 'present', 'Manual Entry', NOW())");
                    $stmt->execute([
                        $userId,
                        $date . ' ' . $checkIn,
                        $checkOut ? $date . ' ' . $checkOut : null
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Manual attendance recorded']);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    private function clockIn($userId, $latitude, $longitude) {
        try {
            // Check if already clocked in today
            $existing = $this->getTodayAttendance($userId);
            if ($existing && !$existing['check_out']) {
                return ['success' => false, 'error' => 'Already clocked in today'];
            }
            
            // Check if on approved leave
            if ($this->checkIfOnLeave($userId)) {
                return ['success' => false, 'error' => 'You are on approved leave today'];
            }
            
            // Get attendance rules and validate location if required
            $rules = $this->getAttendanceRules();
            if ($rules['is_gps_required'] && $latitude && $longitude) {
                $distance = $this->calculateDistance(
                    $latitude, $longitude,
                    $rules['office_latitude'], $rules['office_longitude']
                );
                
                if ($distance > $rules['office_radius_meters']) {
                    return [
                        'success' => false,
                        'error' => "You are {$distance}m away from office. Please move closer."
                    ];
                }
            }
            
            // Determine status (on time or late)
            $shift = $this->getUserShift($userId);
            $status = $this->determineStatus($shift);
            
            // Insert attendance record
            $stmt = $this->db->prepare("
                INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name, status, shift_id, ip_address, device_info, created_at) 
                VALUES (?, NOW(), ?, ?, 'Office', ?, ?, ?, ?, NOW())
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $result = $stmt->execute([
                $userId, $latitude, $longitude, $status, 
                $shift['id'] ?? 1, $ipAddress, $deviceInfo
            ]);
            
            if ($result) {
                // Notify if late
                if ($status === 'late') {
                    $this->notifyLateArrival($userId);
                }
                
                return [
                    'success' => true,
                    'message' => 'Clocked in successfully',
                    'status' => $status,
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
            
            // Calculate total hours
            $checkIn = new DateTime($attendance['check_in']);
            $checkOut = new DateTime();
            $interval = $checkOut->diff($checkIn);
            $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
            
            // Update attendance record
            $stmt = $this->db->prepare("
                UPDATE attendance 
                SET check_out = NOW(), total_hours = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([round($totalHours, 2), $attendance['id']]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Clocked out successfully',
                    'total_hours' => round($totalHours, 2),
                    'time' => date('H:i:s')
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to clock out'];
            
        } catch (Exception $e) {
            error_log('Clock out error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred'];
        }
    }
    
    private function getUserAttendance($userId, $filter) {
        try {
            $dateCondition = $this->getDateCondition($filter);
            
            $stmt = $this->db->prepare("
                SELECT a.*, u.name as user_name, COALESCE(d.name, 'Not Assigned') as department 
                FROM attendance a 
                LEFT JOIN users u ON a.user_id = u.id 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE a.user_id = ? AND $dateCondition 
                ORDER BY a.check_in DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserAttendance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getEmployeeAttendance($role, $filterDate, $currentUserId) {
        try {
            $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user')" : "u.role = 'user'";
            
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    COALESCE(d.name, 'Not Assigned') as department,
                    a.check_in,
                    a.check_out,
                    a.total_hours,
                    CASE 
                        WHEN a.location_name = 'On Approved Leave' THEN 'On Leave'
                        WHEN a.check_in IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                WHERE $roleFilter AND u.id != ?
                ORDER BY u.role DESC, u.name
            ");
            $stmt->execute([$filterDate, $currentUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getEmployeeAttendance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getTodayAttendance($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM attendance 
            WHERE user_id = ? AND DATE(check_in) = CURDATE()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function checkIfOnLeave($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM leaves 
                WHERE user_id = ? AND status = 'approved' 
                AND CURDATE() BETWEEN start_date AND end_date
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch() ? true : false;
        } catch (Exception $e) {
            // If leaves table doesn't exist, assume not on leave
            return false;
        }
    }
    
    private function getAttendanceRules() {
        try {
            $stmt = $this->db->query("SELECT * FROM attendance_rules LIMIT 1");
            $rules = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rules) {
                return [
                    'office_latitude' => 0,
                    'office_longitude' => 0,
                    'office_radius_meters' => 200,
                    'is_gps_required' => 1,
                    'grace_period_minutes' => 15
                ];
            }
            
            return $rules;
        } catch (Exception $e) {
            return [
                'office_latitude' => 0,
                'office_longitude' => 0,
                'office_radius_meters' => 200,
                'is_gps_required' => 1,
                'grace_period_minutes' => 15
            ];
        }
    }
    
    private function getUserShift($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.* FROM shifts s 
                JOIN users u ON u.shift_id = s.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shift) {
                return ['id' => 1, 'start_time' => '09:00:00', 'grace_period' => 15];
            }
            
            return $shift;
        } catch (Exception $e) {
            return ['id' => 1, 'start_time' => '09:00:00', 'grace_period' => 15];
        }
    }
    
    private function determineStatus($shift) {
        $currentTime = date('H:i:s');
        $shiftStart = $shift['start_time'];
        $graceMinutes = $shift['grace_period'] ?? 15;
        
        $shiftStartWithGrace = date('H:i:s', strtotime($shiftStart . ' +' . $graceMinutes . ' minutes'));
        
        return $currentTime > $shiftStartWithGrace ? 'late' : 'present';
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c);
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
        $remainingMinutes = (int)($totalMinutes % 60);
        
        return [
            'total_hours' => $totalHours,
            'total_minutes' => $remainingMinutes,
            'present_days' => $presentDays
        ];
    }
    
    private function notifyLateArrival($userId) {
        try {
            require_once __DIR__ . '/../helpers/NotificationHelper.php';
            $stmt = $this->db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                NotificationHelper::notifyOwners(
                    $userId,
                    'attendance',
                    'late_arrival',
                    "{$user['name']} arrived late at " . date('H:i'),
                    null
                );
            }
        } catch (Exception $e) {
            error_log('Late arrival notification error: ' . $e->getMessage());
        }
    }
    
    private function renderAttendanceTable($employees) {
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
                
                echo "<td>" . ($employee['check_in'] ? date('H:i', strtotime($employee['check_in'])) : '<span style="color: #6b7280;">-</span>') . "</td>";
                
                if ($employee['check_out']) {
                    echo "<td><span style='color: #dc2626; font-weight: 500;'>" . date('H:i', strtotime($employee['check_out'])) . "</span></td>";
                } elseif ($employee['check_in']) {
                    echo "<td><span style='color: #f59e0b; font-weight: 500;'>Working...</span></td>";
                } else {
                    echo "<td><span style='color: #6b7280;'>-</span></td>";
                }
                
                $totalHours = $employee['total_hours'] ?? 0;
                echo "<td>" . ($totalHours > 0 ? "<span style='color: #1f2937; font-weight: 500;'>" . number_format($totalHours, 2) . "h</span>" : "<span style='color: #6b7280;'>0h</span>") . "</td>";
                
                echo "<td><div style='display: flex; gap: 0.25rem;'>";
                echo "<button class='btn btn--sm btn--secondary' onclick='viewEmployeeDetails({$employee['id']})' title='View Details'><span>👁️</span></button>";
                if ($employee['status'] === 'Absent') {
                    echo "<button class='btn btn--sm btn--warning' onclick='markManualAttendance({$employee['id']})' title='Manual Entry'><span>✏️</span></button>";
                }
                echo "</div></td></tr>";
            }
        }
        echo "</tbody></table>";
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
                // Table doesn't exist, create it without foreign key constraint for now
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
                        shift_id INT NULL,
                        total_hours DECIMAL(5,2) NULL,
                        ip_address VARCHAR(45) NULL,
                        device_info TEXT NULL,
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