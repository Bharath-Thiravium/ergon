<?php
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $attendance = [];
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $this->ensureAttendanceTable($db);
            
            $role = $_SESSION['role'] ?? 'user';
            if ($role === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a LEFT JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 30");
                $stmt->execute([$_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
                $stmt->execute();
            }
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Attendance index error: ' . $e->getMessage());
        }
        
        $this->view('attendance/index', ['attendance' => $attendance, 'active_page' => 'attendance']);
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
    
    private function ensureAttendanceTable($db) {
        // Table already exists with correct structure
        return true;
    }
}
?>
