<?php
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        
        if ($role === 'user') {
            // User view - show only their attendance
            $attendance = [];
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a LEFT JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 30");
                $stmt->execute([$_SESSION['user_id']]);
                $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Attendance index error: ' . $e->getMessage());
            }
            
            $this->view('attendance/index', ['attendance' => $attendance, 'active_page' => 'attendance']);
        } else {
            // Admin/Owner view - show employees or all users based on role
            $employeeAttendance = [];
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $this->ensureAttendanceTable($db);
                
                // Get date filter from query parameter
                $filterDate = $_GET['date'] ?? date('Y-m-d');
                
                // Owner sees ALL users (admin + employees), Admin sees only employees
                $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user')" : "u.role = 'user'";
                
                // Get users with attendance status for selected date
                $stmt = $db->prepare("
                    SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.role,
                        COALESCE(d.name, u.department, 'Not Assigned') as department,
                        a.check_in,
                        a.check_out,
                        CASE 
                            WHEN a.check_in IS NOT NULL THEN 'Present'
                            ELSE 'Absent'
                        END as status,
                        CASE 
                            WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                                ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                            ELSE 0
                        END as total_hours
                    FROM users u
                    LEFT JOIN departments d ON u.department = d.id
                    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                    WHERE $roleFilter AND u.id != ?
                    ORDER BY u.role DESC, u.name
                ");
                $stmt->execute([$filterDate, $_SESSION['user_id']]);
                $employeeAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
                        echo "<td><span class='badge badge--$statusBadge'>$statusIcon {$employee['status']}</span></td>";
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
                'active_page' => 'attendance',
                'filter_date' => $filterDate,
                'user_role' => $role
            ]);
        }
    }
    
    public function clock() {
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
                    // Check if already clocked in today
                    $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'error' => 'Already clocked in today']);
                        exit;
                    }
                    
                    // Clock in
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, clock_in, latitude, longitude, location, status, created_at) VALUES (?, NOW(), ?, ?, 'Office', 'present', NOW())");
                    $result = $stmt->execute([$userId, $latitude, $longitude]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to clock in']);
                    }
                    
                } elseif ($type === 'out') {
                    // Find today's clock in record
                    $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL");
                    $stmt->execute([$userId]);
                    $attendance = $stmt->fetch();
                    
                    if (!$attendance) {
                        echo json_encode(['success' => false, 'error' => 'No clock in record found for today']);
                        exit;
                    }
                    
                    // Clock out
                    $stmt = $db->prepare("UPDATE attendance SET clock_out = NOW(), updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$attendance['id']]);
                    
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
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Server error occurred']);
                exit;
            }
        }
        
        // GET request - show clock page
        $todayAttendance = null;
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureAttendanceTable($db);
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(clock_in) = CURDATE()");
            $stmt->execute([$_SESSION['user_id']]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Today attendance fetch error: ' . $e->getMessage());
        }
        
        $this->view('attendance/clock', ['today_attendance' => $todayAttendance, 'active_page' => 'attendance']);
    }
    
    public function status() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()");
            $stmt->execute([$_SESSION['user_id']]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'can_clock_in' => !$todayAttendance,
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
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location, created_at) VALUES (?, ?, ?, 'present', 'Manual Entry', NOW())");
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
        // Enhanced tables are created via schema
        return true;
    }
}
?>
