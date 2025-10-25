<?php
require_once __DIR__ . '/../helpers/SessionManager.php';

class AuthMiddleware {
    public static function requireAuth() {
        SessionManager::start();
        
        // Check if session is valid
        if (!SessionManager::isValid()) {
            self::redirectToLogin();
            return;
        }
        
        // Check if session is expired
        if (SessionManager::isExpired()) {
            SessionManager::destroy();
            self::redirectToLogin('timeout=1');
            return;
        }
        
        // Update last activity
        SessionManager::updateActivity();
        
        // Set strongest no-cache headers to prevent back button access
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . md5(time()) . '"');
    }
    
    public static function requireRole($requiredRole) {
        self::requireAuth();
        
        if ($_SESSION['role'] !== $requiredRole) {
            header('Location: /ergon/dashboard');
            exit;
        }
    }
    
    private static function redirectToLogin($query = '') {
        $url = '/ergon/login';
        if ($query) {
            $url .= '?' . $query;
        }
        header('Location: ' . $url);
        exit;
    }
}
?>