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
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Email and password are required'], 400);
            return;
        }
        
        require_once __DIR__ . '/../services/SecurityService.php';
        $securityService = new SecurityService();
        
        // Check rate limiting
        if (!$securityService->checkRateLimit($clientIp, 'login')) {
            $this->json(['error' => 'Too many login attempts. Please try again later.'], 429);
            return;
        }
        
        // Check account lockout
        $lockoutStatus = $securityService->checkAccountLockout($email);
        if ($lockoutStatus['locked']) {
            $this->json(['error' => $lockoutStatus['message']], 423);
            return;
        }
        
        try {
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                // Record successful login
                $securityService->recordLoginAttempt($email, true);
                $securityService->logAttempt($clientIp, 'login', true);
                
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
                // Record failed login
                $securityService->recordLoginAttempt($email, false);
                $securityService->logAttempt($clientIp, 'login', false);
                
                $remainingAttempts = $lockoutStatus['remaining_attempts'] - 1;
                $message = 'Invalid email or password';
                if ($remainingAttempts <= 2 && $remainingAttempts > 0) {
                    $message .= ". {$remainingAttempts} attempts remaining before account lockout.";
                }
                
                $this->json(['error' => $message], 401);
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $securityService->logAttempt($clientIp, 'login', false);
            $this->json(['error' => 'Login failed. Please try again.'], 500);
        }
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        header('Location: /ergon/login');
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
            
            // Enhanced password validation
            $passwordErrors = $this->validatePassword($newPassword);
            if (!empty($passwordErrors)) {
                $this->json(['error' => implode(', ', $passwordErrors)], 400);
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
    
    public function forgotPassword() {
        if ($this->isPost()) {
            $email = trim($_POST['email'] ?? '');
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json(['error' => 'Valid email address is required'], 400);
                return;
            }
            
            require_once __DIR__ . '/../services/SecurityService.php';
            $securityService = new SecurityService();
            
            // Check rate limiting for password reset requests
            if (!$securityService->checkRateLimit($clientIp, 'password_reset')) {
                $this->json(['error' => 'Too many password reset requests. Please try again later.'], 429);
                return;
            }
            
            $securityService->logAttempt($clientIp, 'password_reset', true);
            
            // Always return success to prevent email enumeration
            $this->json([
                'success' => true, 
                'message' => 'If an account with this email exists, you will receive password reset instructions shortly.'
            ]);
            
            // Process reset and send email
            $resetToken = $this->userModel->initiatePasswordReset($email);
            if ($resetToken) {
                require_once __DIR__ . '/../services/EmailService.php';
                $emailService = new EmailService();
                $user = $this->userModel->getUserByEmail($email);
                if ($user) {
                    $emailService->sendPasswordResetEmail($email, $user['name'], $resetToken);
                }
            }
        } else {
            $this->view('auth/forgot-password');
        }
    }
    
    private function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
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
