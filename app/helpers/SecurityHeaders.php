<?php
class SecurityHeaders {
    public static function apply() {
        // CSP - Strict policy for XSS protection with map support
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: *.openstreetmap.org *.tile.openstreetmap.org; font-src 'self'; connect-src 'self' *.openstreetmap.org nominatim.openstreetmap.org; frame-ancestors 'none';");
        
        // Additional security headers
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("X-Frame-Options: DENY");
        
        // HSTS for HTTPS (only if HTTPS is detected)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}
?>
