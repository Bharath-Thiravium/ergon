<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/User.php';

class AdminManagementController {
    private $userModel;
    
    public function __construct() {
        AuthMiddleware::requireAuth();
        AuthMiddleware::requireRole(['owner']);
        $this->userModel = new User();
    }
    
    public function index() {
        $admins = $this->getAdminPositions();
        $availableUsers = $this->getAvailableUsers();
        $departments = $this->getDepartments();
        
        $data = [
            'admins' => $admins,
            'available_users' => $availableUsers,
            'departments' => $departments
        ];
        
        include __DIR__ . '/../views/admin/management.php';
    }
    
    public function assignAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'];
            $department = $_POST['department'] ?? null;
            $permissions = $_POST['permissions'] ?? [];
            
            $result = $this->createAdminPosition($userId, $department, $permissions);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            exit;
        }
    }
    
    public function removeAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'];
            $result = $this->removeAdminPosition($userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            exit;
        }
    }
    
    private function getAdminPositions() {
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("
            SELECT u.id, u.name, u.email, u.department, u.created_at,
                   ap.permissions, ap.assigned_department, ap.created_at as admin_since,
                   u.is_system_admin
            FROM users u 
            LEFT JOIN admin_positions ap ON u.id = ap.user_id
            WHERE u.role = 'admin' AND u.status = 'active' AND (u.is_system_admin = FALSE OR u.is_system_admin IS NULL)
            ORDER BY ap.created_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAvailableUsers() {
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("
            SELECT id, name, email, department 
            FROM users 
            WHERE role = 'user' AND status = 'active'
            ORDER BY name
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getDepartments() {
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT * FROM departments ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function createAdminPosition($userId, $department, $permissions) {
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Update user role to admin
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Create admin position record
            $stmt = $conn->prepare("
                INSERT INTO admin_positions (user_id, assigned_department, permissions, is_system_admin, assigned_by, created_at) 
                VALUES (?, ?, ?, FALSE, ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $department, 
                json_encode($permissions), 
                $_SESSION['user_id']
            ]);
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }
    
    private function removeAdminPosition($userId) {
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Update user role back to user
            $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Remove admin position record (only for user-promoted admins)
            $stmt = $conn->prepare("DELETE FROM admin_positions WHERE user_id = ? AND (is_system_admin = FALSE OR is_system_admin IS NULL)");
            $stmt->execute([$userId]);
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }
}
?>