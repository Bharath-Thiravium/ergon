<?php
/**
 * System Admin Controller
 * ERGON - Employee Tracker & Task Manager
 * Handles system-level admin creation (not personal users)
 */

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../../config/database.php';

class SystemAdminController {
    private $userModel;
    
    public function __construct() {
        AuthMiddleware::requireAuth();
        AuthMiddleware::requireRole(['owner']);
        $this->userModel = new User();
    }
    
    public function index() {
        $systemAdmins = $this->getSystemAdmins();
        $data = ['system_admins' => $systemAdmins];
        include __DIR__ . '/../views/admin/system_admin.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminName = $_POST['admin_name'];
            $adminEmail = $_POST['admin_email'];
            $permissions = $_POST['permissions'] ?? [];
            
            // Check if email already exists
            if ($this->userModel->emailExists($adminEmail)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            
            $result = $this->createSystemAdmin($adminName, $adminEmail, $permissions);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
    }
    
    public function deactivate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminId = $_POST['admin_id'];
            $result = $this->deactivateSystemAdmin($adminId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            exit;
        }
    }
    
    private function createSystemAdmin($name, $email, $permissions) {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Generate system admin credentials
            $tempPassword = $this->generateSystemPassword();
            $hashedPassword = Security::hashPassword($tempPassword);
            
            // Create system admin user (check if columns exist)
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, temp_password, is_first_login, password_reset_required) 
                VALUES (?, ?, ?, 'admin', ?, TRUE, TRUE)
            ");
            
            $stmt->execute([
                $name,
                $email,
                $hashedPassword,
                $tempPassword
            ]);
            
            $adminId = $conn->lastInsertId();
            
            // Try to update is_system_admin if column exists
            try {
                $stmt = $conn->prepare("UPDATE users SET is_system_admin = TRUE WHERE id = ?");
                $stmt->execute([$adminId]);
            } catch (Exception $e) {
                // Column doesn't exist, continue
            }
            
            // Create admin permissions record if table exists
            try {
                $stmt = $conn->prepare("
                    INSERT INTO admin_positions (user_id, permissions, is_system_admin, assigned_by, created_at) 
                    VALUES (?, ?, TRUE, ?, NOW())
                ");
                
                $stmt->execute([
                    $adminId,
                    json_encode($permissions),
                    $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                // Table doesn't exist or column missing, continue
            }
            
            $conn->commit();
            
            return [
                'success' => true,
                'admin_id' => $adminId,
                'temp_password' => $tempPassword,
                'message' => 'System admin created successfully'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("System admin creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create system admin: ' . $e->getMessage()
            ];
        }
    }
    
    private function deactivateSystemAdmin($adminId) {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Deactivate user
            $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$adminId]);
            
            // Try to remove admin position if table exists
            try {
                $stmt = $conn->prepare("DELETE FROM admin_positions WHERE user_id = ?");
                $stmt->execute([$adminId]);
            } catch (Exception $e) {
                // Table doesn't exist, continue
            }
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("System admin deactivation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getSystemAdmins() {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Try with is_system_admin column
            $stmt = $conn->query("
                SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login,
                       ap.permissions, ap.created_at as admin_since
                FROM users u 
                LEFT JOIN admin_positions ap ON u.id = ap.user_id
                WHERE u.is_system_admin = TRUE
                ORDER BY u.created_at DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback: get all admins if column doesn't exist
            $stmt = $conn->query("
                SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login,
                       NULL as permissions, u.created_at as admin_since
                FROM users u 
                WHERE u.role = 'admin'
                ORDER BY u.created_at DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    private function generateSystemPassword() {
        return 'ADM' . rand(1000, 9999) . chr(rand(65, 90));
    }
}
?>