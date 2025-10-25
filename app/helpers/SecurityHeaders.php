<?php
/**
 * Security Headers Helper
 */

class SecurityHeaders {
    
    public static function setSecureHeaders() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; connect-src 'self'");
        
        // Security Headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: no-referrer-when-downgrade');
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
        
        // HSTS (only if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        }
        
        // Remove server info
        header_remove('X-Powered-By');
        header_remove('Server');
    }
    
    public static function setSecureCookieParams() {
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}
?>