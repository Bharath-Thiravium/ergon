<?php
require_once __DIR__ . '/../helpers/SessionManager.php';

class AuthMiddleware {
    public static function requireAuth() {
        // session.php (loaded by index.php) already configured cookie params
        // before session_start(). Do NOT call session_set_cookie_params() here
        // — it has no effect once the session is already active.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session is valid
        if (empty($_SESSION['user_id'])) {
            error_log('AuthMiddleware: No user_id in session, redirecting to login');
            self::redirectToLogin();
            return;
        }
        
        // Ensure role is set
        if (empty($_SESSION['role'])) {
            error_log('AuthMiddleware: No role in session for user ' . $_SESSION['user_id']);
            $_SESSION['role'] = 'user'; // Default role
        }
        
        // Check if session is expired (8 hours timeout)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
            session_unset();
            session_destroy();
            self::redirectToLogin('timeout=1');
            return;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    public static function requireRole($requiredRole) {
        self::requireAuth();
        
        $userRole = $_SESSION['role'];
        
        // Allow company_owner to access owner resources
        if ($requiredRole === 'owner' && $userRole === 'company_owner') {
            return;
        }
        
        if ($userRole !== $requiredRole) {
            if (!headers_sent()) {
                header('Location: /ergon/dashboard');
            }
            exit;
        }
    }
    
    private static function redirectToLogin($query = '') {
        $url = '/ergon/login';
        if ($query) {
            $url .= '?' . $query;
        }
        if (!headers_sent()) {
            header('Location: ' . $url);
        }
        exit;
    }
}
?>
