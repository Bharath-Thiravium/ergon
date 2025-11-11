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
            $userModel = new User();
            $users = $userModel->getAll();
            
            $data = ['users' => $users];
            
            include __DIR__ . '/../../views/admin/management.php';
        } catch (Exception $e) {
            error_log('AdminManagement Error: ' . $e->getMessage());
            $data = ['users' => []];
            include __DIR__ . '/../../views/admin/management.php';
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
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = intval($_POST['user_id']);
                
                // Prevent deleting self
                if ($userId === $_SESSION['user_id']) {
                    header('Location: /ergon/admin/management?error=cannot_delete_self');
                    exit;
                }
                
                $db = Database::connect();
                $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'owner'");
                $result = $stmt->execute([$userId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    header('Location: /ergon/admin/management?success=user_deleted');
                } else {
                    header('Location: /ergon/admin/management?error=delete_failed');
                }
                exit;
                
            } catch (Exception $e) {
                error_log('Delete User Error: ' . $e->getMessage());
                header('Location: /ergon/admin/management?error=delete_failed');
                exit;
            }
        }
    }
}
