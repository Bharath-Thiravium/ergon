<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../helpers/Security.php';

class ApiController {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->taskModel = new Task();
    }
    
    /**
     * API Login - Returns JWT token
     */
    public function login() {
        $this->apiLogin();
    }
    
    public function attendance() {
        $this->apiAttendance();
    }
    
    public function tasks() {
        $this->apiTasks();
    }
    
    public function updateTask() {
        $this->apiTaskUpdate();
    }
    
    public function apiLogin() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }
        
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user) {
            $token = Security::generateJWT($user['id'], $user['role']);
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }
    
    /**
     * API Attendance - Clock in/out via mobile
     */
    public function apiAttendance() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $latitude = $input['latitude'] ?? 0;
        $longitude = $input['longitude'] ?? 0;
        
        try {
            if ($action === 'checkin') {
                $result = $this->attendanceModel->checkIn(
                    $payload->user_id,
                    $latitude,
                    $longitude,
                    "Mobile Location: {$latitude}, {$longitude}"
                );
            } elseif ($action === 'checkout') {
                $result = $this->attendanceModel->checkOut($payload->user_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                return;
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record attendance']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    /**
     * API Tasks - Get user tasks
     */
    public function apiTasks() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        try {
            $tasks = $this->taskModel->getUserTasks($payload->user_id);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    /**
     * API Task Update - Update task progress
     */
    public function apiTaskUpdate() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? 0;
        $progress = $input['progress'] ?? 0;
        $comment = $input['comment'] ?? '';
        
        try {
            $result = $this->taskModel->updateProgress($taskId, $payload->user_id, $progress, $comment);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update task']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    public function generateEmployeeId() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            // Get company name from settings
            $stmt = $conn->prepare("SELECT company_name FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
            $companyName = $settings['company_name'] ?? 'ERGON';
            
            // Generate prefix
            $prefix = $this->getCompanyPrefix($companyName);
            
            // Get next employee number
            $stmt = $conn->prepare("SELECT COUNT(*) + 1 as next_num FROM users WHERE employee_id IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch();
            $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
            
            $employeeId = $prefix . $nextNum;
            
            echo json_encode([
                'success' => true,
                'employee_id' => $employeeId
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate Employee ID: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    private function getCompanyPrefix($companyName) {
        $words = explode(' ', strtoupper($companyName));
        $prefix = '';
        
        foreach ($words as $word) {
            if (in_array($word, ['THE', 'AND', 'OF', 'FOR', 'TO', 'IN', 'ON', 'AT', 'BY'])) {
                continue;
            }
            
            $cleanWord = preg_replace('/[^A-Z0-9]/', '', $word);
            
            if (strlen($cleanWord) >= 2) {
                $prefix .= substr($cleanWord, 0, 2);
            } elseif (strlen($cleanWord) == 1) {
                $prefix .= $cleanWord;
            }
        }
        
        if (empty($prefix)) {
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($companyName)), 0, 2);
        }
        
        return $prefix ?: 'EMP';
    }
}
?>