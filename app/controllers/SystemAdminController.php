<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class SystemAdminController extends Controller {
    
    protected function requireAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        $title = 'System Admins';
        $active_page = 'system-admin';
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $admins = [];
        }
        
        $data = ['admins' => $admins];
        
        include __DIR__ . '/../../views/admin/system_admin.php';
    }
    
    public function create() {
        $this->requireAuth();
        
        error_log('SystemAdminController::create called with method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('POST data: ' . json_encode($_POST));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                header('Location: /ergon/system-admin?error=All fields are required');
                exit;
            }
            
            if (strlen($password) < 6) {
                header('Location: /ergon/system-admin?error=Password must be at least 6 characters');
                exit;
            }
            
            try {
                $db = Database::connect();
                
                // Check if email already exists
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    header('Location: /ergon/system-admin?error=Email already exists. Please use a different email address.');
                    exit;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
                $result = $stmt->execute([$name, $email, $hashedPassword]);
                
                if ($result) {
                    error_log('Admin created successfully: ' . $name . ' (' . $email . ')');
                    header('Location: /ergon/system-admin?success=Admin created successfully');
                } else {
                    error_log('Failed to create admin: ' . $name . ' (' . $email . ')');
                    header('Location: /ergon/system-admin?error=Failed to create admin');
                }
                exit;
            } catch (Exception $e) {
                error_log('Create admin error: ' . $e->getMessage());
                header('Location: /ergon/system-admin?error=Failed to create admin: ' . $e->getMessage());
                exit;
            }
        }
    }
    
    public function addAdmin() {
        header('Content-Type: application/json');
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            try {
                $db = Database::connect();
                
                // Check if email already exists
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
                $result = $stmt->execute([$name, $email, $hashedPassword]);
                
                echo json_encode(['success' => $result, 'message' => $result ? 'Admin created successfully' : 'Failed to create admin']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        exit;
    }
    
    public function edit() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminId = $_POST['admin_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($adminId) || empty($name) || empty($email)) {
                header('Location: /ergon/system-admin?error=All fields are required');
                exit;
            }
            
            try {
                $db = Database::connect();
                
                // Check if email exists for other users
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkStmt->execute([$email, $adminId]);
                if ($checkStmt->fetch()) {
                    header('Location: /ergon/system-admin?error=Email already exists');
                    exit;
                }
                
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$name, $email, $hashedPassword, $adminId]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$name, $email, $adminId]);
                }
                
                header('Location: /ergon/system-admin?success=Admin updated successfully');
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/system-admin?error=Email already exists');
                exit;
            }
        }
    }
    
    public function export() {
        $this->requireAuth();
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT name, email, status, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="system_admins_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Email', 'Status', 'Created Date']);
            
            foreach ($admins as $admin) {
                fputcsv($output, [
                    $admin['name'],
                    $admin['email'],
                    $admin['status'],
                    date('Y-m-d H:i:s', strtotime($admin['created_at']))
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            header('Location: /ergon/system-admin?error=Export failed');
            exit;
        }
    }
    
    public function toggleStatus() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminId = $_POST['admin_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($adminId) || !in_array($status, ['active', 'inactive'])) {
                header('Location: /ergon/system-admin?error=Invalid request');
                exit;
            }
            
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
                $stmt->execute([$status, $adminId]);
                
                $action = $status === 'active' ? 'activated' : 'deactivated';
                header("Location: /ergon/system-admin?success=Admin {$action} successfully");
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/system-admin?error=Failed to update status');
                exit;
            }
        }
    }
    
    public function changePassword() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            error_log('SystemAdminController::changePassword called');
            error_log('POST data: ' . json_encode($_POST));
            
            $adminId = $_POST['admin_id'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($adminId) || empty($password) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            try {
                $db = Database::connect();
                
                // Check if admin is terminated
                $checkStmt = $db->prepare("SELECT status FROM users WHERE id = ? AND role = 'admin'");
                $checkStmt->execute([$adminId]);
                $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$admin) {
                    echo json_encode(['success' => false, 'message' => 'Admin not found']);
                    exit;
                }
                
                if ($admin['status'] === 'terminated') {
                    echo json_encode(['success' => false, 'message' => 'Cannot change password for terminated admin']);
                    exit;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
                $result = $stmt->execute([$hashedPassword, $adminId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    error_log('Password changed successfully for admin ID: ' . $adminId);
                    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                } else {
                    error_log('Password change failed for admin ID: ' . $adminId . ', result: ' . ($result ? 'true' : 'false') . ', rowCount: ' . $stmt->rowCount());
                    echo json_encode(['success' => false, 'message' => 'Admin not found or no changes made']);
                }
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()]);
                exit;
            }
        }
    }
    
    public function suspendAdmin() {
        header('Content-Type: application/json');
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminId = $_POST['admin_id'] ?? '';
            
            if (empty($adminId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
                exit;
            }
            
            try {
                $db = Database::connect();
                
                // Check if admin exists and current status
                $checkStmt = $db->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'admin'");
                $checkStmt->execute([$adminId]);
                $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$admin) {
                    echo json_encode(['success' => false, 'message' => 'Admin not found']);
                    exit;
                }
                
                // Suspend the admin
                $stmt = $db->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ? AND role = 'admin'");
                $result = $stmt->execute([$adminId]);
                
                if ($result) {
                    error_log("Admin {$adminId} status changed from '{$admin['status']}' to 'suspended'");
                    echo json_encode(['success' => true, 'message' => 'Admin suspended successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to suspend admin']);
                }
                exit;
            } catch (Exception $e) {
                error_log('Suspend admin error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to suspend admin']);
                exit;
            }
        }
    }
}