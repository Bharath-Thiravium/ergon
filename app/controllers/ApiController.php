<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';

class ApiController extends Controller {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->taskModel = new Task();
    }
    
    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Email and password required'], 400);
            return;
        }
        
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user) {
            $this->json([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            $this->json(['error' => 'Invalid credentials'], 401);
        }
    }
    
    public function attendance() {
        $data = [
            'user_id' => $_POST['user_id'] ?? '',
            'type' => $_POST['type'] ?? 'in',
            'latitude' => $_POST['latitude'] ?? null,
            'longitude' => $_POST['longitude'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($this->attendanceModel->clockInOut($data)) {
            $this->json(['success' => true, 'message' => 'Attendance recorded']);
        } else {
            $this->json(['error' => 'Failed to record attendance'], 400);
        }
    }
    
    public function tasks() {
        $userId = $_GET['user_id'] ?? '';
        if (empty($userId)) {
            $this->json(['error' => 'User ID required'], 400);
            return;
        }
        
        $tasks = $this->taskModel->getByUserId($userId);
        $this->json(['tasks' => $tasks]);
    }
    
    public function updateTask() {
        $id = $_POST['id'] ?? '';
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? '',
            'priority' => $_POST['priority'] ?? ''
        ];
        
        if ($this->taskModel->update($id, $data)) {
            $this->json(['success' => true, 'message' => 'Task updated']);
        } else {
            $this->json(['error' => 'Failed to update task'], 400);
        }
    }
    
    public function activityLog() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $action = $input['action'] ?? $_POST['action'] ?? '';
            $details = $input['details'] ?? $_POST['details'] ?? '';
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->json(['error' => 'User not authenticated'], 401);
                return;
            }
            
            require_once __DIR__ . '/../models/ActivityLog.php';
            $activityLog = new ActivityLog();
            
            $result = $activityLog->log($userId, $action, $details);
            
            if ($result) {
                $this->json(['success' => true, 'message' => 'Activity logged']);
            } else {
                $this->json(['error' => 'Failed to log activity'], 500);
            }
        } catch (Exception $e) {
            error_log('Activity log error: ' . $e->getMessage());
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    public function generateEmployeeId() {
        try {
            $stmt = $this->userModel->conn->prepare("SELECT COUNT(*) + 1 as next_num FROM users WHERE employee_id IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
            
            $this->json(['employee_id' => 'EMP' . $nextNum]);
        } catch (Exception $e) {
            $this->json(['employee_id' => 'EMP' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)]);
        }
    }
    
    public function updatePreference() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $key = $input['key'] ?? '';
            $value = $input['value'] ?? '';
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->json(['error' => 'User not authenticated'], 401);
                return;
            }
            
            require_once __DIR__ . '/../models/UserPreference.php';
            $preference = new UserPreference();
            
            if ($preference->set($userId, $key, $value)) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Failed to update preference'], 500);
            }
        } catch (Exception $e) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    public function sessionFromJWT() {
        $this->json(['error' => 'JWT session not implemented'], 501);
    }
    
    public function test() {
        $this->json(['status' => 'API working', 'timestamp' => date('Y-m-d H:i:s')]);
    }
    
    public function registerDevice() {
        $this->json(['error' => 'Device registration not implemented'], 501);
    }
    
    public function syncOfflineData() {
        $this->json(['error' => 'Offline sync not implemented'], 501);
    }
}
?>
