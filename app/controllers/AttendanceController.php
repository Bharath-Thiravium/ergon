<?php
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

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
            ob_clean();
            header('Content-Type: application/json');
            
            try {
                $type = $_POST['type'] ?? '';
                $latitude = $_POST['latitude'] ?? 0;
                $longitude = $_POST['longitude'] ?? 0;
                $userId = $_SESSION['user_id'];
                
                if ($type === 'in') {
                    $coords = Security::validateGPSCoordinate($latitude, $longitude);
                    
                    if (!$coords) {
                        echo json_encode(['success' => false, 'error' => 'Invalid GPS coordinates']);
                        exit;
                    }
                    
                    $result = $this->attendanceModel->checkIn(
                        $userId,
                        $coords['lat'],
                        $coords['lng'],
                        'Office'
                    );
                    echo json_encode(['success' => $result, 'message' => $result ? 'Clocked in successfully' : 'Already clocked in']);
                } elseif ($type === 'out') {
                    $result = $this->attendanceModel->checkOut($userId);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Clocked out successfully' : 'Not clocked in']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Server error']);
            }
            exit;
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