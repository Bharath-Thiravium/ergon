<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';

class ProfileController extends Controller {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $user = $this->getUserProfile($_SESSION['user_id']);
        
        $data = [
            'user' => $user,
            'active_page' => 'profile'
        ];
        
        $this->view('profile/index', $data);
    }
    
    public function update() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            
            $updateData = [
                'name' => Security::sanitizeString($_POST['name'] ?? ''),
                'email' => Security::validateEmail($_POST['email'] ?? ''),
                'phone' => Security::sanitizeString($_POST['phone'] ?? ''),
                'address' => Security::sanitizeString($_POST['address'] ?? '', 500)
            ];
            
            if (empty($updateData['name']) || !$updateData['email']) {
                $data = ['error' => 'Name and valid email are required', 'active_page' => 'profile'];
                $this->view('profile/index', $data);
                return;
            }
            
            if ($this->updateUserProfile($userId, $updateData)) {
                $_SESSION['user_name'] = $updateData['name'];
                $_SESSION['user_email'] = $updateData['email'];
                header('Location: /Ergon/profile?success=1');
            } else {
                header('Location: /Ergon/profile?error=1');
            }
            exit;
        }
        
        $this->index();
    }
    
    public function changePassword() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $data = ['error' => 'All password fields are required', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                $data = ['error' => 'New passwords do not match', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if (strlen($newPassword) < 6) {
                $data = ['error' => 'Password must be at least 6 characters', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if ($this->verifyCurrentPassword($_SESSION['user_id'], $currentPassword)) {
                if ($this->updatePassword($_SESSION['user_id'], $newPassword)) {
                    header('Location: /Ergon/profile?password_changed=1');
                } else {
                    $data = ['error' => 'Failed to update password', 'active_page' => 'profile'];
                    $this->view('profile/change-password', $data);
                }
            } else {
                $data = ['error' => 'Current password is incorrect', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
            }
            exit;
        }
        
        $data = ['active_page' => 'profile'];
        $this->view('profile/change-password', $data);
    }
    
    public function preferences() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $preferences = [
                'theme' => Security::sanitizeString($_POST['theme'] ?? 'light'),
                'language' => Security::sanitizeString($_POST['language'] ?? 'en'),
                'notifications' => isset($_POST['notifications']) ? 1 : 0
            ];
            
            if ($this->updateUserPreferences($_SESSION['user_id'], $preferences)) {
                header('Location: /Ergon/profile/preferences?success=1');
            } else {
                header('Location: /Ergon/profile/preferences?error=1');
            }
            exit;
        }
        
        $preferences = $this->getUserPreferences($_SESSION['user_id']);
        
        $data = [
            'preferences' => $preferences,
            'active_page' => 'profile'
        ];
        
        $this->view('profile/preferences', $data);
    }
    
    private function getUserProfile($userId) {
        try {
            $sql = "SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserProfile error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function updateUserProfile($userId, $data) {
        try {
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $userId
            ]);
        } catch (Exception $e) {
            error_log('updateUserProfile error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function verifyCurrentPassword($userId, $password) {
        try {
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user && password_verify($password, $user['password']);
        } catch (Exception $e) {
            error_log('verifyCurrentPassword error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function updatePassword($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (Exception $e) {
            error_log('updatePassword error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function getUserPreferences($userId) {
        try {
            $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [
                'theme' => 'light',
                'language' => 'en',
                'notifications' => 1
            ];
        } catch (Exception $e) {
            error_log('getUserPreferences error: ' . $e->getMessage());
            return ['theme' => 'light', 'language' => 'en', 'notifications' => 1];
        }
    }
    
    private function updateUserPreferences($userId, $preferences) {
        try {
            $sql = "INSERT INTO user_preferences (user_id, theme, language, notifications) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    theme = VALUES(theme),
                    language = VALUES(language),
                    notifications = VALUES(notifications)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $userId,
                $preferences['theme'],
                $preferences['language'],
                $preferences['notifications']
            ]);
        } catch (Exception $e) {
            error_log('updateUserPreferences error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
