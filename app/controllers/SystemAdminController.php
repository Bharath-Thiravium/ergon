<?php

class SystemAdminController extends Controller {
    
    public function index() {
        $this->requireAuth(['owner']);
        
        $title = 'System Admins';
        $active_page = 'system-admin';
        
        // Mock data for testing
        $data = [
            'admins' => [
                [
                    'id' => 1,
                    'name' => 'John Admin',
                    'email' => 'john@ergon.com',
                    'status' => 'active',
                    'created_at' => '2024-01-15 10:30:00'
                ],
                [
                    'id' => 2,
                    'name' => 'Sarah Manager',
                    'email' => 'sarah@ergon.com',
                    'status' => 'active',
                    'created_at' => '2024-01-10 14:20:00'
                ]
            ]
        ];
        
        include __DIR__ . '/../../views/admin/system_admin.php';
    }
    
    public function create() {
        $this->requireAuth(['owner']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO users (name, email, role, status, created_at) VALUES (?, ?, 'admin', 'active', NOW())");
                $stmt->execute([$_POST['name'], $_POST['email']]);
                
                $this->redirect('/ergon/public/system-admin');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to create admin');
            }
        }
    }
    
    public function deactivate() {
        $this->requireAuth(['owner']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'admin'");
                $stmt->execute([$_POST['admin_id']]);
                
                $this->redirect('/ergon/public/system-admin');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to deactivate admin');
            }
        }
    }
}
