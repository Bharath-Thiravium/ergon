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
}
?>