<?php
require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../models/User.php';

class DepartmentController {
    private $departmentModel;
    private $userModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->departmentModel = new Department();
        $this->userModel = new User();
    }
    
    public function index() {
        $departments = $this->departmentModel->getAll();
        $stats = $this->departmentModel->getStats();
        
        $data = [
            'departments' => $departments,
            'stats' => $stats
        ];
        
        include __DIR__ . '/../views/departments/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->departmentModel->create($_POST);
            if ($result) {
                // Ensure proper redirect without 404
                header('Location: /ergon/departments');
                exit;
            } else {
                $error = 'Failed to create department';
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users, 'error' => $error ?? null];
        include __DIR__ . '/../views/departments/create.php';
    }
    
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->departmentModel->update($id, $_POST);
            if ($result) {
                header('Location: /ergon/departments?success=updated');
                exit;
            }
        }
        
        $department = $this->departmentModel->getById($id);
        $users = $this->userModel->getAll();
        $data = ['department' => $department, 'users' => $users];
        include __DIR__ . '/../views/departments/edit.php';
    }
}
?>