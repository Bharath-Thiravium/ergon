<?php
/**
 * Hostinger-specific optimizations
 * Include this file to handle Hostinger hosting environment issues
 */

// Detect if running on Hostinger
function isHostingerEnvironment() {
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    
    return strpos($docRoot, '/home/') === 0 || 
           strpos($serverName, 'hostinger') !== false ||
           strpos($docRoot, '/public_html/') !== false;
}

if (isHostingerEnvironment()) {
    // Session optimizations for Hostinger
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', 1440);
        ini_set('session.save_path', '/tmp');
        
        // Start session with error handling
        try {
            session_start();
        } catch (Exception $e) {
            error_log('Hostinger session start error: ' . $e->getMessage());
        }
    }
    
    // Database connection optimizations
    ini_set('mysql.connect_timeout', 30);
    ini_set('default_socket_timeout', 30);
    
    // Memory and execution time optimizations
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 30);
    
    // Error reporting for production
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/storage/logs/hostinger_errors.log');
    
    // Output buffering for better performance
    if (!ob_get_level()) {
        ob_start();
    }
}

/**
 * Hostinger-specific session restart function
 */
function hostingerSessionRestart() {
    if (isHostingerEnvironment() && session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
        session_start();
    }
}

/**
 * Hostinger-specific database connection with retry
 */
function hostingerDatabaseConnect($maxRetries = 3) {
    if (!isHostingerEnvironment()) {
        return null;
    }
    
    $retries = 0;
    while ($retries < $maxRetries) {
        try {
            require_once __DIR__ . '/app/config/database.php';
            return Database::connect();
        } catch (Exception $e) {
            $retries++;
            error_log("Hostinger DB connection attempt {$retries} failed: " . $e->getMessage());
            if ($retries < $maxRetries) {
                sleep(1); // Wait 1 second before retry
            }
        }
    }
    
    throw new Exception('Failed to connect to database after ' . $maxRetries . ' attempts');
}
?>