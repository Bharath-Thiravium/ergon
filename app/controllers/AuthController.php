<?php
/**
 * Authentication Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Handle login request
     */
    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->jsonResponse(['error' => 'Invalid CSRF token'], 400);
                return;
            }
            
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Skip rate limiting for now
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // Validate input
            if (empty($email) || empty($password)) {
                $this->jsonResponse(['error' => 'Email and password are required'], 400);
                return;
            }
            
            // Debug authentication
            error_log("Login attempt: email=$email");
            
            // Authenticate user
            $user = $this->userModel->authenticate($email, $password);
            
            error_log("Authentication result: " . ($user ? 'SUCCESS' : 'FAILED'));
            
            if ($user) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['last_activity'] = time();
                
                // Generate JWT token
                $jwt = Security::generateJWT($user['id'], $user['role']);
                
                // Log successful login
                AuthMiddleware::logAuthAttempt(
                    $user['id'], 
                    true, 
                    $clientIP, 
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => SUCCESS_LOGIN,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ],
                    'token' => $jwt,
                    'redirect' => '/ergon/dashboard.php'
                ]);
            } else {
                // Log failed login
                AuthMiddleware::logAuthAttempt(
                    null, 
                    false, 
                    $clientIP, 
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                $this->jsonResponse(['error' => 'Invalid email or password'], 401);
            }
        } else {
            // Show login form
            $this->showLoginForm();
        }
    }
    
    /**
     * Handle logout request
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        AuthMiddleware::logout();
        
        // Log logout
        if ($userId) {
            AuthMiddleware::logAuthAttempt(
                $userId, 
                true, 
                Security::getClientIP(), 
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        }
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
        } else {
            header('Location: /ergon/login');
            exit;
        }
    }
    
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword === $confirmPassword && strlen($newPassword) >= 6) {
                if ($this->userModel->resetPassword($_SESSION['user_id'], $newPassword)) {
                    $_SESSION['password_reset_required'] = false;
                    $_SESSION['is_first_login'] = false;
                    header('Location: /ergon/dashboard?password_reset=1');
                    exit;
                }
            }
        }
        
        include __DIR__ . '/../views/auth/reset-password.php';
    }
    
    /**
     * Show login form
     */
    private function showLoginForm() {
        $_SESSION['csrf_token'] = Security::generateCSRFToken();
        include __DIR__ . '/../views/auth/login.php';
        exit;
    }
    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case ROLE_OWNER:
                return '/ergon/dashboard/owner.php';
            case ROLE_ADMIN:
                return '/ergon/dashboard/admin.php';
            case ROLE_USER:
                return '/ergon/dashboard/user.php';
            default:
                return '/ergon/dashboard/';
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new AuthController();
    
    switch ($_GET['action']) {
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
} else {
    $controller = new AuthController();
    $controller->login();
}
?>