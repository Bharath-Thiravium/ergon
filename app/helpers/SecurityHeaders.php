<?php
class SecurityHeaders {
    public static function setSecureHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    public static function setSecureCookieParams() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
    }
}
?>