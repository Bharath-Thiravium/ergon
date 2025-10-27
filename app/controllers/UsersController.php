<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class UsersController extends Controller {
    
    public function index() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $userModel = new User();
            $users = $userModel->getAll();
            
            $data = [
                'users' => $users,
                'active_page' => 'users'
            ];
            
            $this->view('users/index', $data);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    
    public function viewUser($id) {
        session_start();
        $userModel = new User();
        $user = $userModel->getById($id);
        
        if (!$user) {
            header('Location: /ergon/users?error=user_not_found');
            exit;
        }
        
        $data = ['user' => $user, 'active_page' => 'users'];
        $this->view('users/view', $data);
    }
    
    public function edit($id) {
        session_start();
        $userModel = new User();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel->update($id, $_POST);
            header('Location: /ergon/users?success=updated');
            exit;
        }
        
        $user = $userModel->getById($id);
        if (!$user) {
            header('Location: /ergon/users?error=user_not_found');
            exit;
        }
        
        $data = ['user' => $user, 'active_page' => 'users'];
        $this->view('users/edit', $data);
    }
    
    public function create() {
        session_start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel = new User();
            $result = $userModel->createEnhanced($_POST);
            
            if ($result) {
                $_SESSION['new_credentials'] = [
                    'email' => $_POST['email'],
                    'password' => $result['temp_password']
                ];
                header('Location: /ergon/users?success=created');
                exit;
            }
        }
        
        $this->view('users/create', ['active_page' => 'users']);
    }
    
    public function resetPassword() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
            http_response_code(401);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'];
            $tempPassword = 'RST' . rand(1000, 9999);
            
            $userModel = new User();
            $user = $userModel->getById($userId);
            
            if ($user) {
                // Direct database update to avoid any model complications
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $_SESSION['reset_credentials'] = [
                    'email' => $user['email'],
                    'password' => $tempPassword
                ];
                
                error_log("Password reset for user {$userId}: {$tempPassword}");
                
                // Test the password immediately
                if (password_verify($tempPassword, $hashedPassword)) {
                    error_log("✅ Password verification successful for user {$userId}");
                } else {
                    error_log("❌ Password verification failed for user {$userId}");
                }
            }
        }
        
        header('Location: /ergon/users');
        exit;
    }
    
    public function downloadCredentials() {
        session_start();
        
        $credentials = $_SESSION['new_credentials'] ?? $_SESSION['reset_credentials'] ?? null;
        
        if (!$credentials) {
            header('Location: /ergon/users');
            exit;
        }
        
        $content = "ERGON User Credentials\n\n";
        $content .= "Username: " . $credentials['email'] . "\n";
        $content .= "Password: " . $credentials['password'] . "\n\n";
        $content .= "Please change password on first login.";
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="user_credentials.txt"');
        echo $content;
        
        unset($_SESSION['new_credentials'], $_SESSION['reset_credentials']);
        exit;
    }
}
?>