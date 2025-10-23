<?php
require_once __DIR__ . '/../models/Attendance.php';

class AttendanceController {
    private $attendanceModel;
    
    public function __construct() {
        $this->attendanceModel = new Attendance();
    }
    
    public function index() {
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
        
        include __DIR__ . '/../views/attendance/index.php';
    }
    
    public function clock() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ensure clean JSON output
            ob_clean();
            header('Content-Type: application/json');
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $action = $input['action'] ?? '';
                $userId = $_SESSION['user_id'] ?? null;
                
                if (!$userId) {
                    echo json_encode(['success' => false, 'message' => 'Not logged in']);
                    exit;
                }
                
                if ($action === 'clock_in') {
                    $result = $this->attendanceModel->checkIn(
                        $userId,
                        $input['latitude'] ?? 0,
                        $input['longitude'] ?? 0,
                        $input['location_name'] ?? 'Office'
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
        include __DIR__ . '/../views/attendance/clock.php';
    }
}
?>