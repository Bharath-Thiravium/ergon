<?php
require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class DepartmentController extends Controller {
    private $departmentModel;
    private $userModel;
    
    public function __construct() {
        $this->departmentModel = new Department();
        $this->userModel = new User();
    }
    
    public function index() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $departments = $this->departmentModel->getAllWithStats();
            $stats = $this->departmentModel->getStats();
            
            $data = [
                'departments' => $departments,
                'stats' => $stats
            ];
            
            $title = 'Department Management';
            $active_page = 'departments';
            
            include __DIR__ . '/../../views/departments/index.php';
            
        } catch (Exception $e) {
            error_log('Department index error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to load departments';
            header('Location: /ergon/dashboard');
            exit;
        }
    }
    
    public function create() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Security::validateCSRFToken($_POST['csrf_token']);
                
                $data = [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? ''),
                    'head_id' => !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null,
                    'status' => $_POST['status'] ?? 'active'
                ];
                
                if ($this->departmentModel->create($data)) {
                    $_SESSION['success'] = 'Department created successfully';
                } else {
                    $_SESSION['error'] = 'Failed to create department';
                }
                
                header('Location: /ergon/departments');
                exit;
            } catch (Exception $e) {
                error_log('Department create error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to create department';
                header('Location: /ergon/departments');
                exit;
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users];
        
        $title = 'Create Department';
        $active_page = 'departments';
        
        include __DIR__ . '/../../views/departments/create.php';
    }
    
    public function edit($id) {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $department = $this->departmentModel->findById($id);
        if (!$department) {
            $_SESSION['error'] = 'Department not found';
            header('Location: /ergon/departments');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Security::validateCSRFToken($_POST['csrf_token']);
                
                $data = [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? ''),
                    'head_id' => !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null,
                    'status' => $_POST['status'] ?? 'active'
                ];
                
                if ($this->departmentModel->update($id, $data)) {
                    $_SESSION['success'] = 'Department updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update department';
                }
                
                header('Location: /ergon/departments');
                exit;
            } catch (Exception $e) {
                error_log('Department update error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update department';
                header('Location: /ergon/departments');
                exit;
            }
        }
        
        $users = $this->userModel->getAll();
        $data = [
            'department' => $department,
            'users' => $users
        ];
        
        $title = 'Edit Department';
        $active_page = 'departments';
        
        include __DIR__ . '/../../views/departments/edit.php';
    }
    
    public function delete($id) {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            if ($this->departmentModel->delete($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete department']);
            }
        } catch (Exception $e) {
            error_log('Department delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete department']);
        }
        exit;
    }
    
    public function store() {
        $this->create();
    }
    
    public function viewDepartment($id) {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            // Get department with head information
            $stmt = $db->prepare("
                SELECT d.*, u.name as head_name, u.email as head_email, u.phone as head_phone
                FROM departments d 
                LEFT JOIN users u ON d.head_id = u.id 
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$department) {
                $_SESSION['error'] = 'Department not found';
                header('Location: /ergon/departments');
                exit;
            }
            
            // Get department statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_employees
                FROM users u 
                WHERE u.department_id = ?
            ");
            $stmt->execute([$id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get department employees
            $stmt = $db->prepare("
                SELECT u.id, u.name, u.email, u.role, u.status, u.designation
                FROM users u 
                WHERE u.department_id = ?
                ORDER BY u.name
            ");
            $stmt->execute([$id]);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'department' => $department,
                'stats' => $stats,
                'employees' => $employees
            ];
            $title = 'Department Details';
            $active_page = 'departments';
            
            include __DIR__ . '/../../views/departments/view.php';
        } catch (Exception $e) {
            error_log('Department view error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to load department details';
            header('Location: /ergon/departments');
            exit;
        }
    }
    
    public function editPost() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['department_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $head_id = !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null;
            $status = $_POST['status'] ?? 'active';
            
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE departments SET name = ?, description = ?, head_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $head_id, $status, $id]);
                
                header('Location: /ergon/departments?success=Department updated successfully');
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/departments?error=Failed to update department');
                exit;
            }
        }
    }
    
    public function deletePost() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['department_id'] ?? '';
            
            try {
                $db = Database::connect();
                $stmt = $db->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->execute([$id]);
                
                header('Location: /ergon/departments?success=Department deleted successfully');
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/departments?error=Failed to delete department');
                exit;
            }
        }
    }
}
