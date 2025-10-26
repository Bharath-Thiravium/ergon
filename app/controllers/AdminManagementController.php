<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

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
}
