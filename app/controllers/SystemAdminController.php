<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class SystemAdminController extends Controller {
    
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                header('Location: /ergon/system-admin?error=All fields are required');
                exit;
            }
            
            $db = Database::connect();
            
            // Check if email already exists with a more robust query
            $checkStmt = $db->query("SELECT email FROM users WHERE email = '$email' LIMIT 1");
            if ($checkStmt && $checkStmt->fetch()) {
                header('Location: /ergon/system-admin?error=Email already exists. Please use a different email address.');
                exit;
            }
            
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
                $stmt->execute([$name, $email, $hashedPassword]);
                header('Location: /ergon/system-admin?success=Admin created successfully');
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/system-admin?error=Email already exists. Please use a different email address.');
                exit;
            }
        }
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
    
    public function deactivate() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminId = $_POST['admin_id'] ?? '';
            
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'admin'");
                $stmt->execute([$adminId]);
                
                header('Location: /ergon/system-admin');
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/system-admin?error=' . urlencode($e->getMessage()));
                exit;
            }
        }
    }
}