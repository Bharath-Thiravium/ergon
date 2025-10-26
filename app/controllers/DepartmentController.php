<?php

class DepartmentController extends Controller {
    
    public function index() {
        $this->requireAuth(['admin', 'owner']);
        
        $title = 'Department Management';
        $active_page = 'departments';
        
        try {
            $departments = [
                ['id' => 1, 'name' => 'Information Technology', 'code' => 'IT', 'head' => 'John Doe', 'employees' => 12, 'status' => 'active'],
                ['id' => 2, 'name' => 'Human Resources', 'code' => 'HR', 'head' => 'Jane Smith', 'employees' => 5, 'status' => 'active'],
                ['id' => 3, 'name' => 'Sales & Marketing', 'code' => 'SALES', 'head' => 'Mike Johnson', 'employees' => 8, 'status' => 'active'],
                ['id' => 4, 'name' => 'Accounts & Finance', 'code' => 'ACCOUNTS', 'head' => 'Sarah Wilson', 'employees' => 4, 'status' => 'active']
            ];
            
            $data = ['departments' => $departments];
            
            ob_start();
            include __DIR__ . '/../../views/departments/index.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to load departments');
        }
    }
    
    public function create() {
        $this->requireAuth(['admin', 'owner']);
        
        $title = 'Create Department';
        $active_page = 'departments';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Mock creation - would normally save to database
                $this->redirect('/ergon_clean/public/departments');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to create department');
            }
        }
        
        ob_start();
        include __DIR__ . '/../../views/departments/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/dashboard.php';
    }
    
    public function store() {
        $this->create();
    }
}