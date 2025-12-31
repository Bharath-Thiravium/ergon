<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/LocationService.php';

class ApiController extends Controller {
    
    public function userProjects() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $projects = LocationService::getUserProjects($_SESSION['user_id']);
            echo json_encode(['success' => true, 'projects' => $projects]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function serviceHistory() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $stmt = $db->prepare("
                SELECT sh.*, p.name as project_name, p.latitude as project_lat, p.longitude as project_lng
                FROM service_history sh
                JOIN projects p ON sh.project_id = p.id
                WHERE sh.user_id = ? AND sh.service_date BETWEEN ? AND ?
                ORDER BY sh.service_date DESC, sh.start_time DESC
            ");
            $stmt->execute([$userId, $startDate, $endDate]);
            $history = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function departments() {
        // Set session cookie parameters to match AuthMiddleware
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $domain = $_SERVER['HTTP_HOST'];
            session_set_cookie_params([
                'lifetime' => 28800,
                'path' => '/ergon/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        // Check auth
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure departments table exists
            $db->exec("CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create default departments if none exist
            if (empty($departments)) {
                $defaultDepts = ['Human Resources', 'Information Technology', 'Finance', 'Marketing', 'Operations', 'Sales'];
                $insertStmt = $db->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                foreach ($defaultDepts as $dept) {
                    $insertStmt->execute([$dept, 'Default department']);
                }
                // Fetch again
                $stmt->execute();
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'departments' => $departments]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function projects() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $departmentId = $_GET['department_id'] ?? null;
            
            if ($departmentId) {
                // Filter projects by department
                $stmt = $db->prepare("SELECT p.id, p.name, p.description, p.department_id, d.name as department_name FROM projects p LEFT JOIN departments d ON p.department_id = d.id WHERE p.status = 'active' AND p.department_id = ? ORDER BY p.name");
                $stmt->execute([$departmentId]);
            } else {
                // Get all active projects
                $stmt = $db->prepare("SELECT p.id, p.name, p.description, p.department_id, d.name as department_name FROM projects p LEFT JOIN departments d ON p.department_id = d.id WHERE p.status = 'active' ORDER BY p.name");
                $stmt->execute();
            }
            
            $projects = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'projects' => $projects]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function getExpense() {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$expense) {
                echo json_encode(['success' => false, 'error' => 'Expense not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'expense' => $expense]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
        exit;
    }
    
    public function getAdvance() {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM advances WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                echo json_encode(['success' => false, 'error' => 'Advance not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'advance' => $advance]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
        exit;
    }
    
    public function getUser($userId = null) {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            // Debug logging
            error_log('getUser called with userId: ' . var_export($userId, true));
            error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
            
            // Fallback: extract user ID from URL if not passed as parameter
            if (!$userId) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $path = str_replace('/ergon', '', $path);
                if (preg_match('/\/api\/users\/(\d+)/', $path, $matches)) {
                    $userId = $matches[1];
                    error_log('Extracted userId from URL: ' . $userId);
                }
            }
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required', 'debug' => ['userId' => $userId, 'uri' => $_SERVER['REQUEST_URI']]]);
                exit;
            }
            
            $stmt = $db->prepare("SELECT id, name, email, role, department_id, phone, date_of_birth, gender, designation, joining_date, salary, address, emergency_contact, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            // Get user's assigned projects
            $stmt = $db->prepare("SELECT project_id FROM user_projects WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $userProjects = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'user' => $user, 'user_projects' => $userProjects]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function contactPersons() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure contacts table exists
            $db->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $stmt = $db->prepare("SELECT id, name, phone, email, company FROM contacts ORDER BY name");
            $stmt->execute();
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'contacts' => $contacts]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function users() {
        // Set session cookie parameters to match AuthMiddleware
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $domain = $_SERVER['HTTP_HOST'];
            session_set_cookie_params([
                'lifetime' => 28800,
                'path' => '/ergon/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        // Check auth
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function taskCategories() {
        // Set session cookie parameters to match AuthMiddleware
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $domain = $_SERVER['HTTP_HOST'];
            session_set_cookie_params([
                'lifetime' => 28800,
                'path' => '/ergon/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        // Check auth
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $departmentId = $_GET['department_id'] ?? null;
            
            if (!$departmentId) {
                echo json_encode(['success' => false, 'error' => 'Department ID required']);
                exit;
            }
            
            // Create task_categories table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS task_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(100) NOT NULL,
                department_id INT NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_department (department_id)
            )");
            
            // Remove department_name column if it exists (it shouldn't be there)
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM task_categories LIKE 'department_name'");
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $db->exec("ALTER TABLE task_categories DROP COLUMN department_name");
                }
            } catch (Exception $e) {
                error_log('Error removing department_name column: ' . $e->getMessage());
            }
            
            // Check if department_id column exists, add if missing
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM task_categories LIKE 'department_id'");
                $stmt->execute();
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE task_categories ADD COLUMN department_id INT NOT NULL DEFAULT 1");
                    // Check if index exists before adding
                    $indexStmt = $db->prepare("SHOW INDEX FROM task_categories WHERE Key_name = 'idx_department'");
                    $indexStmt->execute();
                    if ($indexStmt->rowCount() == 0) {
                        $db->exec("ALTER TABLE task_categories ADD INDEX idx_department (department_id)");
                    }
                }
            } catch (Exception $e) {
                error_log('Task categories table modification error: ' . $e->getMessage());
            }
            
            // Check if we have categories for this department
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM task_categories WHERE department_id = ?");
            $stmt->execute([$departmentId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                // Create default categories for this department
                $this->createDefaultCategories($db, $departmentId);
            }
            
            $stmt = $db->prepare("SELECT category_name, description FROM task_categories WHERE department_id = ? ORDER BY category_name");
            $stmt->execute([$departmentId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    private function createDefaultCategories($db, $departmentId) {
        // Get department name to create relevant categories
        $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dept) return;
        
        $deptName = strtolower($dept['name']);
        $categories = [];
        
        // Define categories based on department type
        if (strpos($deptName, 'hr') !== false || strpos($deptName, 'human') !== false) {
            $categories = [
                'Recruitment', 'Employee Onboarding', 'Performance Review', 'Training & Development',
                'Policy Updates', 'Employee Relations', 'Compliance', 'Benefits Administration'
            ];
        } elseif (strpos($deptName, 'it') !== false || strpos($deptName, 'tech') !== false || strpos($deptName, 'information') !== false) {
            $categories = [
                'Software Development', 'System Maintenance', 'Bug Fixes', 'Security Updates',
                'Database Management', 'Network Administration', 'User Support', 'Infrastructure'
            ];
        } elseif (strpos($deptName, 'finance') !== false || strpos($deptName, 'accounting') !== false) {
            $categories = [
                'Budget Planning', 'Financial Reporting', 'Invoice Processing', 'Audit Preparation',
                'Tax Compliance', 'Expense Management', 'Financial Analysis', 'Accounts Reconciliation'
            ];
        } elseif (strpos($deptName, 'marketing') !== false || strpos($deptName, 'sales') !== false) {
            $categories = [
                'Campaign Planning', 'Content Creation', 'Lead Generation', 'Client Follow-up',
                'Market Research', 'Brand Management', 'Social Media', 'Event Planning'
            ];
        } elseif (strpos($deptName, 'operations') !== false) {
            $categories = [
                'Process Improvement', 'Quality Control', 'Vendor Management', 'Logistics',
                'Inventory Management', 'Customer Service', 'Operational Planning', 'Compliance'
            ];
        } else {
            // Generic categories for other departments
            $categories = [
                'Planning', 'Execution', 'Review', 'Documentation',
                'Communication', 'Analysis', 'Reporting', 'Follow-up'
            ];
        }
        
        $insertStmt = $db->prepare("INSERT INTO task_categories (category_name, department_id, description) VALUES (?, ?, ?)");
        foreach ($categories as $category) {
            try {
                $insertStmt->execute([$category, $departmentId, "Default category for {$dept['name']} department"]);
            } catch (Exception $e) {
                error_log('Error inserting category: ' . $e->getMessage());
            }
        }
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

}
?>
