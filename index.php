<?php
/**
 * ERGON - Employee Tracker & Task Manager
 * Main Entry Point
 */

// Configure and start session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);
session_start();

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/error.log');
error_reporting(E_ALL);

// Remove server information
header_remove('X-Powered-By');
header_remove('Server');

// Include configuration
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/app/config/database.php';

// Include core classes
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/helpers/Security.php';

// Set cache headers
if (preg_match('/\/(dashboard|owner|admin|user)\//', $_SERVER['REQUEST_URI'] ?? '')) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

try {
    // Debug output
    error_log('ERGON: Index.php executed - URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    
    // Initialize router
    $router = new Router();
    
    // Load routes
    require_once __DIR__ . '/app/config/routes.php';
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    error_log('ERGON Error: ' . $e->getMessage());
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>System Error</title></head>";
    echo "<body><h1>System Error</h1><p>Please try again later.</p>";
    echo "<a href='/Ergon/public/login'>Return to Login</a></body></html>";
}
?>
