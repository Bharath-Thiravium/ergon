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
            $userId = $_POST['user_id'] ?? null;
            $department = $_POST['department'] ?? null;
            $permissions = $_POST['permissions'] ?? [];
            
            if (!$userId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'User ID is required']);
                exit;
            }
            
            $result = $this->createAdminPosition($userId, $department, $permissions);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            exit;
        }
        
        // If not POST, redirect to management page
        header('Location: /ergon/admin/management');
        exit;
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
        
        try {
            // Try with is_system_admin column
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
        } catch (Exception $e) {
            // Fallback: get all admins if column doesn't exist
            $stmt = $conn->query("
                SELECT u.id, u.name, u.email, u.department, u.created_at,
                       NULL as permissions, NULL as assigned_department, u.created_at as admin_since,
                       FALSE as is_system_admin
                FROM users u 
                WHERE u.role = 'admin' AND u.status = 'active'
                ORDER BY u.created_at DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
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
            
            // Try to create admin position record if table exists
            try {
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
            } catch (Exception $e) {
                // Table doesn't exist or column missing, continue
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Admin position creation error: " . $e->getMessage());
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
            
            // Try to remove admin position record if table exists
            try {
                $stmt = $conn->prepare("DELETE FROM admin_positions WHERE user_id = ?");
                $stmt->execute([$userId]);
            } catch (Exception $e) {
                // Table doesn't exist, continue
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Admin position removal error: " . $e->getMessage());
            return false;
        }
    }
}
?>