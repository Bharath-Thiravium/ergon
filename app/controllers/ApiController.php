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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $action = $input['action'] ?? $_POST['action'] ?? 'page_view';
            $details = $input['details'] ?? $_POST['details'] ?? 'User activity';
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->json(['success' => false, 'error' => 'User not authenticated'], 401);
                return;
            }
            
            // Try to log activity, but don't fail if table doesn't exist
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Check if table exists
                $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
                if ($stmt->rowCount() == 0) {
                    // Create table if it doesn't exist
                    $sql = "CREATE TABLE activity_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        action VARCHAR(255) NOT NULL,
                        details TEXT,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id)
                    )";
                    $db->exec($sql);
                }
                
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([
                    $userId,
                    $action,
                    $details,
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                $this->json(['success' => true, 'message' => 'Activity logged']);
            } catch (Exception $dbError) {
                error_log('Activity log DB error: ' . $dbError->getMessage());
                // Return success even if logging fails to not break user experience
                $this->json(['success' => true, 'message' => 'Activity noted']);
            }
            
        } catch (Exception $e) {
            error_log('Activity log error: ' . $e->getMessage());
            $this->json(['success' => true, 'message' => 'Request processed'], 200);
        }
    }
    
    public function generateEmployeeId() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get the highest existing employee ID number
            $stmt = $db->prepare("SELECT employee_id FROM users WHERE employee_id LIKE 'EMP%' ORDER BY employee_id DESC LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['employee_id']) {
                // Extract number from existing ID (e.g., EMP001 -> 001)
                $lastNum = intval(substr($result['employee_id'], 3));
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }
            
            $employeeId = 'EMP' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            
            // Check if this ID already exists (safety check)
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_id = ?");
            $checkStmt->execute([$employeeId]);
            
            if ($checkStmt->fetchColumn() > 0) {
                // If exists, generate a random one
                $employeeId = 'EMP' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            }
            
            $this->json(['employee_id' => $employeeId]);
        } catch (Exception $e) {
            error_log('Generate Employee ID error: ' . $e->getMessage());
            $this->json(['employee_id' => 'EMP' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT)]);
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
    
    public function taskCategories() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $departmentId = $_GET['department_id'] ?? null;
            
            if (!$departmentId) {
                $this->json(['error' => 'Department ID is required'], 400);
                return;
            }
            
            // Get department name first
            $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$department) {
                $this->json(['error' => 'Department not found'], 404);
                return;
            }
            
            // Get task categories for this department
            $stmt = $db->prepare("SELECT DISTINCT category_name, description FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
            $stmt->execute([$department['name']]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->json(['categories' => $categories]);
            
        } catch (Exception $e) {
            error_log('Task categories API error: ' . $e->getMessage());
            $this->json(['error' => 'Failed to fetch categories'], 500);
        }
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
