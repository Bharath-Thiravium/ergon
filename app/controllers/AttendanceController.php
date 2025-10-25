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
                'attendance' => $this->attendanceModel->getUserAttendance($_SESSION['user_id'])
            ];
        } else {
            $data = [
                'attendance' => $this->attendanceModel->getAll()
            ];
        }
        
        $this->view('attendance/index', $data);
    }
    
    public function clock() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ensure clean JSON output
            ob_clean();
            header('Content-Type: application/json');
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validate CSRF token for JSON requests
                if (!Security::validateCSRFToken($input['csrf_token'] ?? '')) {
                    echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
                    exit;
                }
                
                $action = Security::sanitizeString($input['action'] ?? '');
                $userId = $_SESSION['user_id'];
                
                if ($action === 'clock_in') {
                    $coords = Security::validateGPSCoordinate(
                        $input['latitude'] ?? 0,
                        $input['longitude'] ?? 0
                    );
                    
                    if (!$coords) {
                        echo json_encode(['success' => false, 'message' => 'Invalid GPS coordinates']);
                        exit;
                    }
                    
                    $result = $this->attendanceModel->checkIn(
                        $userId,
                        $coords['lat'],
                        $coords['lng'],
                        Security::sanitizeString($input['location_name'] ?? 'Office')
                    );
                    echo json_encode(['success' => $result, 'message' => $result ? 'Clocked in successfully' : 'Already clocked in']);
                } elseif ($action === 'clock_out') {
                    $result = $this->attendanceModel->checkOut($userId);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Clocked out successfully' : 'Not clocked in']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Server error']);
            }
            exit;
        }
        $this->view('attendance/clock');
    }
}
?>