<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/TimezoneHelper.php';

class SimpleAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
    }
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        $userId = $_SESSION['user_id'];
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        
        // Query to get all users with their attendance data for the selected date
        if ($role === 'user') {
            $roleFilter = "AND u.id = $userId";
        } elseif ($role === 'admin') {
            $roleFilter = "AND u.role != 'owner'";
        } else {
            $roleFilter = "";
        }
        
        // Convert times from IST to display format (times are already in IST in database)
        $this->db->exec("SET time_zone = '+05:30'");
        
        if ($selectedDate === date('Y-m-d')) {
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
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                WHERE u.status != 'removed' {$roleFilter}
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
                WHERE u.status != 'removed' {$roleFilter}
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
        
        $this->view('attendance/index', [
            'attendance' => $attendance,
            'stats' => ['total_hours' => 0, 'total_minutes' => 0, 'present_days' => 0],
            'current_filter' => 'today',
            'selected_date' => $selectedDate,
            'user_role' => $role,
            'active_page' => 'attendance',
            'is_grouped' => $isGrouped
        ]);
    }
    
    public function status() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$_SESSION['user_id'], $currentDate]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'attendance' => $todayAttendance,
                'on_leave' => false,
                'can_clock_in' => !$todayAttendance,
                'can_clock_out' => $todayAttendance && !$todayAttendance['check_out']
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function clock() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $type = $_POST['type'] ?? '';
                $userId = $_SESSION['user_id'];
                
                header('Content-Type: application/json');
                
                if ($type === 'in') {
                    $currentTime = TimezoneHelper::nowIst();
                    
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, created_at) VALUES (?, ?, ?)");
                    $result = $stmt->execute([$userId, $currentTime, $currentTime]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to clock in']);
                    }
                    
                } elseif ($type === 'out') {
                    $currentTime = TimezoneHelper::nowIst();
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
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to clock out']);
                    }
                }
                exit;
                
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
                exit;
            }
        }
        
        // GET request - show clock page
        $this->view('attendance/clock', ['active_page' => 'attendance']);
    }
    
    public function manual() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = intval($_POST['user_id']);
                $checkIn = $_POST['check_in'] ?? null;
                $checkOut = $_POST['check_out'] ?? null;
                $date = $_POST['date'] ?? date('Y-m-d');
                
                // Store times in IST format
                $checkInIST = $checkIn ? $date . ' ' . $checkIn : null;
                $checkOutIST = $checkOut ? $date . ' ' . $checkOut : null;
                
                $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $this->db->prepare("UPDATE attendance SET check_in = ?, check_out = ? WHERE id = ?");
                    $stmt->execute([$checkInIST, $checkOutIST, $existing['id']]);
                } else {
                    $stmt = $this->db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at) VALUES (?, ?, ?, 'present', 'Manual Entry', NOW())");
                    $stmt->execute([$userId, $checkInIST, $checkOutIST]);
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
}
?>