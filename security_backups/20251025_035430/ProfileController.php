<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';

class ProfileController {
    private $userModel;
    
    public function __construct() {
        AuthMiddleware::requireAuth();
        $this->userModel = new User();
    }
    
    public function index() {
        $user = $this->userModel->getById($_SESSION['user_id']);
        $data = ['user' => $user];
        include __DIR__ . '/../views/profile/index.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updateData = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ];
            
            if ($this->userModel->update($_SESSION['user_id'], $updateData)) {
                $_SESSION['user_name'] = $updateData['name'];
                header('Location: /ergon/profile?success=updated');
                exit;
            }
        }
        $this->index();
    }
    
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// [SECURITY FIX] Removed hardcoded password: $currentPassword = $_POST['current_password'] ?? '';
// [SECURITY FIX] Removed hardcoded password: $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $user = $this->userModel->getById($_SESSION['user_id']);
            
            if (!Security::verifyPassword($currentPassword, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters';
            } else {
                if ($this->userModel->update($_SESSION['user_id'], ['password' => $newPassword])) {
                    header('Location: /ergon/profile?success=password_changed');
                    exit;
                } else {
                    $error = 'Failed to update password';
                }
            }
            
            $data = ['error' => $error];
            include __DIR__ . '/../views/profile/change-password.php';
        } else {
            include __DIR__ . '/../views/profile/change-password.php';
        }
    }
    
    public function preferences() {
        try {
            require_once __DIR__ . '/../models/UserPreference.php';
            $preferenceModel = new UserPreference();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = [
                    'theme' => $_POST['theme'] ?? 'light',
                    'language' => $_POST['language'] ?? 'en',
                    'timezone' => $_POST['timezone'] ?? 'UTC',
                    'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
                    'notifications_browser' => isset($_POST['notifications_browser']) ? '1' : '0',
                    'dashboard_layout' => $_POST['dashboard_layout'] ?? 'default'
                ];
                
                $result = $preferenceModel->updateMultiplePreferences($_SESSION['user_id'], $preferences);
                
                if ($result) {
                    header('Location: /ergon/profile/preferences?success=updated');
                    exit;
                } else {
                    $error = 'Failed to update preferences';
                }
            }
            
            $preferences = $preferenceModel->getUserPreferences($_SESSION['user_id']);
            // Ensure all keys exist with defaults
            $preferences = array_merge([
                'theme' => 'light',
                'language' => 'en', 
                'timezone' => 'UTC',
                'notifications_email' => '1',
                'notifications_browser' => '1',
                'dashboard_layout' => 'default'
            ], $preferences);
            $data = ['preferences' => $preferences, 'error' => $error ?? null];
            include __DIR__ . '/../views/profile/preferences.php';
        } catch (Exception $e) {
            error_log("Preferences controller error: " . $e->getMessage());
            $data = [
                'preferences' => [
                    'theme' => 'light',
                    'language' => 'en',
                    'timezone' => 'UTC', 
                    'notifications_email' => '1',
                    'notifications_browser' => '1',
                    'dashboard_layout' => 'default'
                ], 
                'error' => 'System error occurred'
            ];
            include __DIR__ . '/../views/profile/preferences.php';
        }
    }
}
?>