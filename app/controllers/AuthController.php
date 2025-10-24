<?php
/**
 * Authentication Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }
    
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        
        $_SESSION['csrf_token'] = Security::generateCSRFToken();
        $this->view('auth/login');
    }
    
    public function login() {
        if ($this->isPost()) {
            // Skip CSRF validation for now
            // if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            //     $this->json(['error' => 'Invalid CSRF token'], 400);
            // }
            
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Skip rate limiting for now
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // Validate input
            if (empty($email) || empty($password)) {
                $this->json(['error' => 'Email and password are required'], 400);
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
                $_SESSION['user'] = $user; // Store full user data
                $_SESSION['last_activity'] = time();
                
                // Check if password reset is required
                if ($user['password_reset_required'] || $user['is_first_login']) {
                    $_SESSION['password_reset_required'] = true;
                    
                    $this->json([
                        'success' => true,
                        'message' => 'Password reset required',
                        'redirect' => '/ergon/auth/reset-password'
                    ]);
                    return;
                }
                
                // Generate JWT token
                $jwt = Security::generateJWT($user['id'], $user['role']);
                
                // Log successful login
                AuthMiddleware::logAuthAttempt(
                    $user['id'], 
                    true, 
                    $clientIP, 
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                $this->json([
                    'success' => true,
                    'message' => SUCCESS_LOGIN,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ],
                    'token' => $jwt,
                    'redirect' => '/ergon/dashboard'
                ]);
            } else {
                // Log failed login
                AuthMiddleware::logAuthAttempt(
                    null, 
                    false, 
                    $clientIP, 
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                $this->json(['error' => 'Invalid email or password'], 401);
            }
        } else {
            $this->showLogin();
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
        
        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Logged out successfully']);
        } else {
            $this->redirect('/login');
        }
    }
    
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $error = 'Both password fields are required';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters';
            } else {
                if ($this->userModel->resetPassword($_SESSION['user_id'], $newPassword)) {
                    unset($_SESSION['password_reset_required']);
                    $this->redirect('/dashboard');
                    return;
                } else {
                    $error = 'Failed to update password';
                }
            }
            
            $data = ['error' => $error];
            include __DIR__ . '/../views/auth/reset-password.php';
        } else {
            include __DIR__ . '/../views/auth/reset-password.php';
        }
    }
    

    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case ROLE_OWNER:
                return '/ergon/owner/dashboard';
            case ROLE_ADMIN:
                return '/ergon/admin/dashboard';
            case ROLE_USER:
                return '/ergon/user/dashboard';
            default:
                return '/ergon/dashboard';
        }
    }
    

}


?>