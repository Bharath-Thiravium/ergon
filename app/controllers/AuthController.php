<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/constants.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }
    
    public function showLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
            return;
        }
        
        $this->view('auth/login');
    }
    
    public function login() {
        if (!$this->isPost()) {
            $this->showLogin();
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Validate input
        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Email and password are required'], 400);
            return;
        }
        
        try {
            // Authenticate user
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                // Start session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                error_log('Login successful - Session data set: ' . json_encode([
                    'user_id' => $user['id'],
                    'role' => $user['role'],
                    'login_time' => $_SESSION['login_time']
                ]));
                
                $redirectUrl = $this->getRedirectUrl($user['role']);
                
                $this->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ],
                    'redirect' => $redirectUrl
                ]);
            } else {
                $this->json(['error' => 'Invalid email or password'], 401);
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $this->json(['error' => 'Login failed. Please try again.'], 500);
        }
    }
    
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        
        // Clear session cookie
        setcookie(session_name(), '', time() - 3600, '/');
        
        // Set no-cache headers
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Redirect to login
        header('Location: /ergon/login');
        exit;
    }
    
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }
        
        if ($this->isPost()) {
            $error = '';
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
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
            $this->view('auth/reset-password', $data);
        } else {
            $this->view('auth/reset-password');
        }
    }
    
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