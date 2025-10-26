<?php
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', 3600);
            session_start();
        }
    }
    
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }
    
    public static function destroy() {
        self::start();
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function isValid() {
        self::start();
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }
    
    public static function updateActivity() {
        self::start();
        $_SESSION['last_activity'] = time();
    }
    
    public static function isExpired() {
        self::start();
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        return (time() - $_SESSION['last_activity']) > 3600;
    }
}
?>
