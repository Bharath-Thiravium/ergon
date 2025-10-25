<?php
/**
 * Secure Session Manager
 * Provides secure session handling with IP validation and regeneration
 */

class SessionManager {
    
    /**
     * Start secure session
     */
    public static function start() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', 3600); // 1 hour
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // IP validation
        if (!isset($_SESSION['ip'])) {
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        } elseif ($_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            self::destroy();
            throw new Exception('Session IP mismatch - possible session hijacking');
        }
        
        // User agent validation
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::destroy();
            throw new Exception('Session user agent mismatch');
        }
    }
    
    /**
     * Destroy session securely
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            session_write_close();
            
            // Clear session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['role']) && 
               isset($_SESSION['login_time']);
    }
    
    /**
     * Require user to be logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /ergon/login');
            exit;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 3600)) { // 1 hour timeout
            self::destroy();
            header('Location: /ergon/login?timeout=1');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Login user securely
     */
    public static function login($user) {
        self::start();
        
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Log successful login
        error_log("User login: {$user['email']} from {$_SERVER['REMOTE_ADDR']}");
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (self::isLoggedIn()) {
            $email = $_SESSION['email'] ?? 'unknown';
            error_log("User logout: {$email} from {$_SERVER['REMOTE_ADDR']}");
        }
        
        self::destroy();
    }
    
    /**
     * Get current user data
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['email']
        ];
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole($role) {
        return self::isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Access denied - insufficient privileges');
        }
    }
}
?>