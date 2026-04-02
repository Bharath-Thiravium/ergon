<?php
class SessionManager {
    public static function start() {
        }
    
    public static function regenerate() {
        // Do not regenerate session ID — it causes cookie loss on Hostinger CDN.
        // The session is already secure as it is started fresh on login.
        self::start();
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
