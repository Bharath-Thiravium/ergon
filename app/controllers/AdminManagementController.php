<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class AdminManagementController extends Controller {
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            // Ensure database schema supports terminated status
            $this->ensureStatusColumn();
            
            $userModel = new User();
            $users = $userModel->getAll();
            
            // Ensure users array is always valid
            if (!is_array($users)) {
                $users = [];
            }
            
            // Add cache headers to prevent stale data
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $data = ['users' => $users];
            
            include __DIR__ . '/../../views/admin/management.php';
        } catch (Exception $e) {
            error_log('AdminManagement Error: ' . $e->getMessage());
            $data = ['users' => []];
            include __DIR__ . '/../../views/admin/management.php';
        }
    }
    
    private function ensureStatusColumn() {
        try {
            $db = Database::connect();
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column && strpos($column['Type'], 'terminated') === false) {
                $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'terminated') DEFAULT 'active'");
                error_log('Updated users status column to support terminated');
            }
        } catch (Exception $e) {
            error_log('Status column update error: ' . $e->getMessage());
        }
    }
    
    public function assignAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND role = 'user'");
                $stmt->execute([$_POST['user_id']]);
                
                header('Location: /ergon/admin/management?success=admin_assigned');
                exit;
            } catch (Exception $e) {
                error_log('Assign Admin Error: ' . $e->getMessage());
                header('Location: /ergon/admin/management?error=assign_failed');
                exit;
            }
        }
    }
    
    public function removeAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'admin'");
                $stmt->execute([$_POST['admin_id']]);
                
                header('Location: /ergon/admin/management?success=admin_removed');
                exit;
            } catch (Exception $e) {
                error_log('Remove Admin Error: ' . $e->getMessage());
                header('Location: /ergon/admin/management?error=remove_failed');
                exit;
            }
        }
    }
    
    public function changePassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = intval($_POST['user_id']);
                $newPassword = $_POST['new_password'];
                
                if (strlen($newPassword) < 6) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
                    exit;
                }
                
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $result = $stmt->execute([$hashedPassword, $userId]);
                
                if ($result) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
                }
                exit;
                
            } catch (Exception $e) {
                error_log('Change Password Error: ' . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Server error occurred']);
                exit;
            }
        }
    }
    
    public function deleteUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = intval($_POST['user_id'] ?? 0);
                
                if ($userId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                    exit;
                }
                
                // Prevent terminating self
                if ($userId === intval($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Cannot terminate yourself']);
                    exit;
                }
                
                $db = Database::connect();
                
                // Check if user exists first
                $checkStmt = $db->prepare("SELECT id, role, status FROM users WHERE id = ?");
                $checkStmt->execute([$userId]);
                $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }
                
                if ($user['role'] === 'owner') {
                    echo json_encode(['success' => false, 'message' => 'Cannot terminate owner']);
                    exit;
                }
                
                // Update user status to terminated
                $stmt = $db->prepare("UPDATE users SET status = 'terminated', updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$userId]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'User terminated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
                }
                exit;
                
            } catch (Exception $e) {
                error_log('Terminate User Error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
}
