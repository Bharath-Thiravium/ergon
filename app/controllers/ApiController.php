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
                // Return default categories if no department specified
                $defaultCategories = [
                    ['category_name' => 'General Task', 'description' => 'General work task'],
                    ['category_name' => 'Follow-up', 'description' => 'Follow-up task'],
                    ['category_name' => 'Meeting', 'description' => 'Meeting or discussion'],
                    ['category_name' => 'Development', 'description' => 'Development work'],
                    ['category_name' => 'Testing', 'description' => 'Testing and QA'],
                    ['category_name' => 'Documentation', 'description' => 'Documentation work']
                ];
                $this->json(['categories' => $defaultCategories]);
                return;
            }
            
            // Check if departments table exists
            $stmt = $db->query("SHOW TABLES LIKE 'departments'");
            if ($stmt->rowCount() == 0) {
                $this->json(['categories' => []]);
                return;
            }
            
            // Get department name first
            $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$department) {
                $this->json(['categories' => []]);
                return;
            }
            
            // Check if task_categories table exists
            $stmt = $db->query("SHOW TABLES LIKE 'task_categories'");
            if ($stmt->rowCount() == 0) {
                // Return department-specific default categories
                $deptCategories = $this->getDefaultCategoriesForDepartment($department['name']);
                $this->json(['categories' => $deptCategories]);
                return;
            }
            
            // Get task categories from database
            $deptName = html_entity_decode($department['name'], ENT_QUOTES, 'UTF-8');
            $stmt = $db->prepare("SELECT category_name, description FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
            $stmt->execute([$deptName]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no categories found, return defaults
            if (empty($categories)) {
                $categories = $this->getDefaultCategoriesForDepartment($department['name']);
            }
            
            $this->json(['categories' => $categories]);
            
        } catch (Exception $e) {
            error_log('Task categories API error: ' . $e->getMessage());
            $defaultCategories = [
                ['category_name' => 'General Task', 'description' => 'General work task'],
                ['category_name' => 'Follow-up', 'description' => 'Follow-up task']
            ];
            $this->json(['categories' => $defaultCategories]);
        }
    }
    
    private function getDefaultCategoriesForDepartment($deptName) {
        $baseCategories = [
            ['category_name' => 'General Task', 'description' => 'General work task'],
            ['category_name' => 'Follow-up', 'description' => 'Follow-up task'],
            ['category_name' => 'Meeting', 'description' => 'Meeting or discussion']
        ];
        
        switch (strtolower($deptName)) {
            case 'information technology':
            case 'it':
                return array_merge($baseCategories, [
                    ['category_name' => 'Development', 'description' => 'Software development'],
                    ['category_name' => 'Bug Fix', 'description' => 'Bug fixing and debugging'],
                    ['category_name' => 'Testing', 'description' => 'Testing and QA']
                ]);
            case 'marketing':
                return array_merge($baseCategories, [
                    ['category_name' => 'Campaign', 'description' => 'Marketing campaign'],
                    ['category_name' => 'Content Creation', 'description' => 'Content creation'],
                    ['category_name' => 'Social Media', 'description' => 'Social media management']
                ]);
            case 'human resources':
            case 'hr':
                return array_merge($baseCategories, [
                    ['category_name' => 'Recruitment', 'description' => 'Recruitment activities'],
                    ['category_name' => 'Training', 'description' => 'Employee training'],
                    ['category_name' => 'Policy Review', 'description' => 'Policy review and updates']
                ]);
            default:
                return $baseCategories;
        }
    }
    
    public function followupDetails() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if tasks table exists and has required columns
            $stmt = $db->query("SHOW TABLES LIKE 'tasks'");
            if ($stmt->rowCount() == 0) {
                $this->json(['followups' => []]);
                return;
            }
            
            // Check if required columns exist
            $stmt = $db->query("SHOW COLUMNS FROM tasks");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $followups = [];
            
            // Try different column combinations based on what exists
            if (in_array('company_name', $columns)) {
                $stmt = $db->query("SELECT DISTINCT company_name, contact_person, project_name, contact_phone FROM tasks WHERE company_name IS NOT NULL AND company_name != '' ORDER BY created_at DESC LIMIT 50");
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Fallback: get task titles as suggestions
                $stmt = $db->query("SELECT DISTINCT title as company_name, '' as contact_person, '' as project_name, '' as contact_phone FROM tasks WHERE title IS NOT NULL ORDER BY created_at DESC LIMIT 20");
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $this->json(['followups' => $followups]);
            
        } catch (Exception $e) {
            error_log('Followup details API error: ' . $e->getMessage());
            $this->json(['followups' => []]);
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
    
    public function contactPersons() {
        try {
            $pdo = Database::connect();
            
            // Ensure contacts table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Get contacts from multiple sources
            $contacts = [];
            
            // 1. From contacts table
            try {
                $contactsFromTable = $pdo->query("
                    SELECT DISTINCT id, name, phone, email, company 
                    FROM contacts 
                    WHERE name IS NOT NULL AND name != '' 
                    ORDER BY name
                ")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $contactsFromTable = [];
            }
            
            // 2. From tasks table (existing followup data)
            try {
                $contactsFromTasks = $pdo->query("
                    SELECT DISTINCT 
                        0 as id,
                        contact_person as name, 
                        contact_phone as phone, 
                        '' as email, 
                        company_name as company 
                    FROM tasks 
                    WHERE contact_person IS NOT NULL AND contact_person != '' 
                    ORDER BY contact_person
                ")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $contactsFromTasks = [];
            }
            
            // Merge and deduplicate contacts
            $allContacts = array_merge($contactsFromTable, $contactsFromTasks);
            $uniqueContacts = [];
            $seen = [];
            
            foreach ($allContacts as $contact) {
                $key = strtolower(trim($contact['name']));
                if (!isset($seen[$key]) && !empty($contact['name'])) {
                    $seen[$key] = true;
                    $uniqueContacts[] = $contact;
                }
            }
            
            $this->json(['success' => true, 'contacts' => $uniqueContacts]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'contacts' => []]);
        }
    }
    
    public function companies() {
        try {
            $pdo = Database::connect();
            
            // Get companies from multiple sources
            $companies = [];
            
            // 1. From contacts table
            $companiesFromContacts = $pdo->query("
                SELECT DISTINCT company as name 
                FROM contacts 
                WHERE company IS NOT NULL AND company != '' 
                ORDER BY company
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. From tasks table (existing followup data)
            $companiesFromTasks = $pdo->query("
                SELECT DISTINCT company_name as name 
                FROM tasks 
                WHERE company_name IS NOT NULL AND company_name != '' 
                ORDER BY company_name
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge and deduplicate companies
            $allCompanies = array_merge($companiesFromContacts, $companiesFromTasks);
            $uniqueCompanies = [];
            $seen = [];
            
            foreach ($allCompanies as $company) {
                $key = strtolower(trim($company['name']));
                if (!isset($seen[$key]) && !empty($company['name'])) {
                    $seen[$key] = true;
                    $uniqueCompanies[] = $company;
                }
            }
            
            $this->json(['success' => true, 'companies' => $uniqueCompanies]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'companies' => []]);
        }
    }
    
    public function users() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get all active users for task assignment
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->json(['success' => true, 'users' => $users]);
            
        } catch (Exception $e) {
            error_log('Users API error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Failed to fetch users', 'users' => []]);
        }
    }
}
?>
