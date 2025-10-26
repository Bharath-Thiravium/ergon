<?php

class AdminManagementController extends Controller {
    
    public function index() {
        $this->requireAuth(['owner']);
        
        $title = 'User Admin Management';
        $active_page = 'admin';
        
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@ergon.com', 'role' => 'admin', 'status' => 'active'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@ergon.com', 'role' => 'user', 'status' => 'active'],
                ['id' => 3, 'name' => 'Mike Johnson', 'email' => 'mike@ergon.com', 'role' => 'user', 'status' => 'active']
            ]
        ];
        
        include __DIR__ . '/../../views/admin/management.php';
    }
    
    public function assignAdmin() {
        $this->requireAuth(['owner']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND role = 'user'");
                $stmt->execute([$_POST['user_id']]);
                
                $this->redirect('/ergon_clean/public/admin/management');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to assign admin role');
            }
        }
    }
    
    public function removeAdmin() {
        $this->requireAuth(['owner']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'admin'");
                $stmt->execute([$_POST['admin_id']]);
                
                $this->redirect('/ergon_clean/public/admin/management');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to remove admin role');
            }
        }
    }
}