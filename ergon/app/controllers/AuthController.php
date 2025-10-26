<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/constants.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        Session::init();
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }
    
    public function showLogin() {
        Session::init();
        if (Session::isLoggedIn()) {
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
        
        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Email and password are required'], 400);
            return;
        }
        
        try {
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                
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
        
        setcookie(session_name(), '', time() - 3600, '/');
        
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        header('Location: /ergon_clean/public/login');
        exit;
    }
    
    public function resetPassword() {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $this->json(['error' => 'Both password fields are required'], 400);
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                $this->json(['error' => 'Passwords do not match'], 400);
                return;
            }
            
            if (strlen($newPassword) < 6) {
                $this->json(['error' => 'Password must be at least 6 characters'], 400);
                return;
            }
            
            if ($this->userModel->resetPassword(Session::get('user_id'), $newPassword)) {
                $this->json(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                $this->json(['error' => 'Failed to update password'], 500);
            }
        } else {
            $this->view('auth/reset-password');
        }
    }
    
    private function getRedirectUrl($role) {
        switch ($role) {
            case ROLE_OWNER:
                return '/ergon_clean/public/owner/dashboard';
            case ROLE_ADMIN:
                return '/ergon_clean/public/admin/dashboard';
            case ROLE_USER:
                return '/ergon_clean/public/user/dashboard';
            default:
                return '/ergon_clean/public/dashboard';
        }
    }
}
?>