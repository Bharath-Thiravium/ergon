<?php
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class AttendanceController extends Controller {
    private $attendanceModel;
    
    public function __construct() {
        $this->attendanceModel = new Attendance();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        
        if ($role === 'user') {
            $data = [
                'attendance' => $this->attendanceModel->getUserAttendance($_SESSION['user_id']),
                'active_page' => 'attendance'
            ];
        } else {
            $data = [
                'attendance' => $this->attendanceModel->getAll(),
                'active_page' => 'attendance'
            ];
        }
        
        $this->view('attendance/index', $data);
    }
    
    public function clock() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $type = $_POST['type'] ?? '';
                $latitude = floatval($_POST['latitude'] ?? 0);
                $longitude = floatval($_POST['longitude'] ?? 0);
                $userId = $_SESSION['user_id'];
                
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                if ($type === 'in') {
                    // Check if already clocked in today
                    $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->fetch()) {
                        header('Location: /ergon/attendance/clock?error=Already clocked in today');
                        exit;
                    }
                    
                    // Clock in
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, clock_in, latitude, longitude, location, created_at) VALUES (?, NOW(), ?, ?, 'Office', NOW())");
                    $result = $stmt->execute([$userId, $latitude, $longitude]);
                    
                    if ($result) {
                        header('Location: /ergon/attendance/clock?success=Clocked in successfully');
                    } else {
                        header('Location: /ergon/attendance/clock?error=Failed to clock in');
                    }
                    
                } elseif ($type === 'out') {
                    // Find today's clock in record
                    $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL");
                    $stmt->execute([$userId]);
                    $attendance = $stmt->fetch();
                    
                    if (!$attendance) {
                        header('Location: /ergon/attendance/clock?error=No clock in record found for today');
                        exit;
                    }
                    
                    // Clock out
                    $stmt = $db->prepare("UPDATE attendance SET clock_out = NOW(), updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$attendance['id']]);
                    
                    if ($result) {
                        header('Location: /ergon/attendance/clock?success=Clocked out successfully');
                    } else {
                        header('Location: /ergon/attendance/clock?error=Failed to clock out');
                    }
                    
                } else {
                    header('Location: /ergon/attendance/clock?error=Invalid action');
                }
                exit;
                
            } catch (Exception $e) {
                error_log('Attendance clock error: ' . $e->getMessage());
                header('Location: /ergon/attendance/clock?error=Server error occurred');
                exit;
            }
        }
        
        $todayAttendance = $this->attendanceModel->getTodayAttendance($_SESSION['user_id']);
        $data = [
            'today_attendance' => $todayAttendance,
            'active_page' => 'attendance'
        ];
        
        $this->view('attendance/clock', $data);
    }
    
    public function conflicts() {
        AuthMiddleware::requireAuth();
        
        $conflicts = $this->attendanceModel->getConflicts();
        $data = [
            'conflicts' => $conflicts,
            'active_page' => 'attendance'
        ];
        
        $this->view('attendance/conflicts', $data);
    }
    
    public function resolveConflict($id) {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE attendance_conflicts SET resolved = 1, resolved_by = ?, resolved_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$_SESSION['user_id'], $id]);
                
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
            exit;
        }
    }
}
?>
