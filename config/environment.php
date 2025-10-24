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
            $isDev = false;
            
            foreach ($devHosts as $devHost) {
                if (strpos($host, $devHost) !== false) {
                    $isDev = true;
                    break;
                }
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
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if (self::isDevelopment()) {
            return $protocol . '://' . $host . '/ergon';
        } else {
            // Hostinger production URL
            return $protocol . '://' . $host . '/ergon';
        }
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