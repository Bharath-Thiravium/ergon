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
    
    public static function setAll() {
        self::setSecureHeaders();
        self::setSecureCookieParams();
        self::setCSP();
        self::setHSTS();
        self::setReferrerPolicy();
        self::setPermissionsPolicy();
        self::removeServerInfo();
    }
    
    public static function setCSP() {
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com code.jquery.com unpkg.com; " .
               "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com; " .
               "connect-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com; " .
               "frame-ancestors 'none';";
        
        header("Content-Security-Policy: {$csp}");
    }
    
    public static function setHSTS() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }
    
    public static function setFrameOptions() {
        header("X-Frame-Options: DENY");
    }
    
    public static function setContentTypeOptions() {
        header("X-Content-Type-Options: nosniff");
    }
    
    public static function setXSSProtection() {
        header("X-XSS-Protection: 1; mode=block");
    }
    
    public static function setReferrerPolicy() {
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
    
    public static function setPermissionsPolicy() {
        $policy = "geolocation=(self), " .
                 "microphone=(), " .
                 "camera=(), " .
                 "payment=(), " .
                 "usb=(), " .
                 "magnetometer=(), " .
                 "gyroscope=()";
        
        header("Permissions-Policy: {$policy}");
    }
    
    public static function removeServerInfo() {
        header_remove('X-Powered-By');
        header_remove('Server');
    }
}
?>