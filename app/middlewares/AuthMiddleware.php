<?php
require_once __DIR__ . '/../helpers/SessionManager.php';

class AuthMiddleware {
    public static function requireAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session is valid
        if (empty($_SESSION['user_id'])) {
            self::redirectToLogin();
            return;
        }
        
        // Check if session is expired
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            session_unset();
            session_destroy();
            self::redirectToLogin('timeout=1');
            return;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Set strongest no-cache headers to prevent back button access (only if headers not sent)
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('ETag: "' . md5(time()) . '"');
        }
    }
    
    public static function requireRole($requiredRole) {
        self::requireAuth();
        
        if ($_SESSION['role'] !== $requiredRole) {
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
