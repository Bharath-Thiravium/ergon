<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../config/database.php';

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
            
            // Check if this is an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if (empty($updateData['name']) || !$updateData['email']) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Name and valid email are required']);
                    exit;
                }
                $data = ['error' => 'Name and valid email are required', 'active_page' => 'profile'];
                $this->view('profile/index', $data);
                return;
            }
            
            if ($this->updateUserProfile($userId, $updateData)) {
                $_SESSION['user_name'] = $updateData['name'];
                $_SESSION['user_email'] = $updateData['email'];
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                    exit;
                }
                header('Location: /ergon/profile?success=1');
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                    exit;
                }
                header('Location: /ergon/profile?error=1');
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
            
            // Check if this is an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'All password fields are required']);
                    exit;
                }
                $data = ['error' => 'All password fields are required', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
                    exit;
                }
                $data = ['error' => 'New passwords do not match', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if (strlen($newPassword) < 6) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                    exit;
                }
                $data = ['error' => 'Password must be at least 6 characters', 'active_page' => 'profile'];
                $this->view('profile/change-password', $data);
                return;
            }
            
            if ($this->verifyCurrentPassword($_SESSION['user_id'], $currentPassword)) {
                if ($this->updatePassword($_SESSION['user_id'], $newPassword)) {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                        exit;
                    }
                    header('Location: /ergon/profile?password_changed=1');
                } else {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to update password']);
                        exit;
                    }
                    $data = ['error' => 'Failed to update password', 'active_page' => 'profile'];
                    $this->view('profile/change-password', $data);
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                    exit;
                }
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
                'dashboard_layout' => Security::sanitizeString($_POST['dashboard_layout'] ?? 'default'),
                'language' => Security::sanitizeString($_POST['language'] ?? 'en'),
                'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'UTC'),
                'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
                'notifications_browser' => isset($_POST['notifications_browser']) ? '1' : '0'
            ];
            
            // Check if this is an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($this->updateUserPreferences($_SESSION['user_id'], $preferences)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
                    exit;
                }
                header('Location: /ergon/profile/preferences?success=1');
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to save preferences']);
                    exit;
                }
                header('Location: /ergon/profile/preferences?error=1');
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
            $sql = "SELECT u.id, u.name, u.email, u.phone, u.address, u.role, u.created_at, 
                           COALESCE(d.name, u.department, 'General') as department
                    FROM users u 
                    LEFT JOIN departments d ON u.department_id = d.id 
                    WHERE u.id = ?";
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
            
            error_log('Retrieved preferences for user ' . $userId . ': ' . json_encode($result));
            
            if ($result) {
                // Convert TINYINT to string for consistency
                $result['notifications_email'] = (string)$result['notifications_email'];
                $result['notifications_browser'] = (string)$result['notifications_browser'];
                return $result;
            }
            
            return [
                'theme' => 'light',
                'dashboard_layout' => 'default',
                'language' => 'en',
                'timezone' => 'UTC',
                'notifications_email' => '1',
                'notifications_browser' => '1'
            ];
        } catch (Exception $e) {
            error_log('getUserPreferences error: ' . $e->getMessage());
            return ['theme' => 'light', 'dashboard_layout' => 'default', 'language' => 'en', 'timezone' => 'UTC', 'notifications_email' => '1', 'notifications_browser' => '1'];
        }
    }
    
    private function updateUserPreferences($userId, $preferences) {
        try {
            // Ensure table exists
            $this->createUserPreferencesTable();
            
            // Debug log
            error_log('Saving preferences for user ' . $userId . ': ' . json_encode($preferences));
            
            $sql = "INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    theme = VALUES(theme),
                    dashboard_layout = VALUES(dashboard_layout),
                    language = VALUES(language),
                    timezone = VALUES(timezone),
                    notifications_email = VALUES(notifications_email),
                    notifications_browser = VALUES(notifications_browser)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $preferences['theme'],
                $preferences['dashboard_layout'],
                $preferences['language'],
                $preferences['timezone'],
                $preferences['notifications_email'],
                $preferences['notifications_browser']
            ]);
            
            // Verify save
            if ($result) {
                $checkSql = "SELECT * FROM user_preferences WHERE user_id = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$userId]);
                $saved = $checkStmt->fetch(PDO::FETCH_ASSOC);
                error_log('Saved preferences: ' . json_encode($saved));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('updateUserPreferences error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function createUserPreferencesTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS user_preferences (
                user_id INT PRIMARY KEY,
                theme VARCHAR(20) DEFAULT 'light',
                dashboard_layout VARCHAR(20) DEFAULT 'default',
                language VARCHAR(10) DEFAULT 'en',
                timezone VARCHAR(50) DEFAULT 'UTC',
                notifications_email TINYINT(1) DEFAULT 1,
                notifications_browser TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $this->db->exec($sql);
        } catch (Exception $e) {
            error_log('createUserPreferencesTable error: ' . $e->getMessage());
        }
    }
}
?>
