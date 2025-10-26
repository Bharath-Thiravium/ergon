<?php
require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';

class DepartmentController extends Controller {
    private $departmentModel;
    private $userModel;
    
    public function __construct() {
        $this->departmentModel = new Department();
        $this->userModel = new User();
    }
    
    public function index() {
        session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            $this->redirect('/ergon/auth/login');
            return;
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
            $this->redirect('/ergon/dashboard');
        }
    }
    
    public function create() {
        session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            $this->redirect('/ergon/auth/login');
            return;
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
                
                $this->redirect('/ergon/departments');
            } catch (Exception $e) {
                error_log('Department create error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to create department';
                $this->redirect('/ergon/departments');
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users];
        
        $title = 'Create Department';
        $active_page = 'departments';
        
        include __DIR__ . '/../../views/departments/create.php';
    }
    
    public function edit($id) {
        session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            $this->redirect('/ergon/auth/login');
            return;
        }
        
        $department = $this->departmentModel->findById($id);
        if (!$department) {
            $_SESSION['error'] = 'Department not found';
            $this->redirect('/ergon/departments');
            return;
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
                
                $this->redirect('/ergon/departments');
            } catch (Exception $e) {
                error_log('Department update error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update department';
                $this->redirect('/ergon/departments');
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
        session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            $this->redirect('/ergon/auth/login');
            return;
        }
        
        try {
            if ($this->departmentModel->delete($id)) {
                $_SESSION['success'] = 'Department deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete department';
            }
        } catch (Exception $e) {
            error_log('Department delete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete department';
        }
        
        $this->redirect('/ergon/departments');
    }
    
    public function store() {
        $this->create();
    }
}
