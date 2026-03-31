<?php
/**
 * Environment Configuration
 * Auto-detects development vs production environment
 */

class Environment {
    private static $environment = null;
    
    public static function detect() {
        if (self::$environment === null) {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            
            // Development indicators
            $devHosts = ['localhost', '127.0.0.1', 'ergon.test', 'ergon.local'];
            $prodHosts = ['bkgreenenergy.com', 'athenas.co.in'];
            $isDev = false;
            
            // Check for development hosts first
            foreach ($devHosts as $devHost) {
                if (strpos($host, $devHost) !== false) {
                    $isDev = true;
                    break;
                }
            }
            
            // If not development, check for production hosts
            if (!isset($isDev) || $isDev !== true) {
                foreach ($prodHosts as $prodHost) {
                    if (strpos($host, $prodHost) !== false) {
                        $isDev = false;
                        break;
                    }
                }
            }
            
            // Default to development if no specific host match
            if (!isset($isDev)) {
                $isDev = true;
            }
            
            // Additional Hostinger detection
            if (!$isDev && (strpos($docRoot, '/home/') === 0 || strpos($docRoot, '/public_html/') !== false)) {
                $isDev = false; // Force production for Hostinger
            }
            
            self::$environment = $isDev ? 'development' : 'production';
        }
        
        return self::$environment;
    }
    
    public static function isDevelopment() {
        return self::detect() === 'development';
    }
    
    public static function isProduction() {
        return self::detect() === 'production';
    }
    
    public static function getBaseUrl() {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Always use the actual request host so subdomain requests
        // (e.g. aes.athenas.co.in) are not incorrectly rewritten to the
        // main domain and cause redirect loops.
        return $protocol . '://' . $host . '/ergon';
    }

    public static function asset($path) {
        return self::getBaseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function isHostinger() {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        
        return strpos($docRoot, '/home/') === 0 || 
               strpos($serverName, 'hostinger') !== false ||
               strpos($docRoot, '/public_html/') !== false;
    }
}
?>
