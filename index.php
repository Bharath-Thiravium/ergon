<?php
/**
 * ERGON - Employee Tracker & Task Manager
 * Main Entry Point
 */

// Load security headers first
require_once __DIR__ . '/app/helpers/SecurityHeaders.php';
SecurityHeaders::setSecureHeaders();
SecurityHeaders::setSecureCookieParams();

// Initialize performance optimizations
require_once __DIR__ . '/app/helpers/PerformanceBooster.php';
PerformanceBooster::init();

// Start session
session_start();

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(E_ALL);

// Remove server information
header_remove('X-Powered-By');
header_remove('Server');

// Include autoloader and configuration
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Include core classes
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/Cache.php';
require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';

// Enable output compression
if (!ob_get_level()) ob_start('ob_gzhandler');

// Set cache headers
header('Cache-Control: public, max-age=300');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');

try {
    // Initialize router
    $router = new Router();
    
    // Load routes
    require_once __DIR__ . '/config/routes.php';
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    error_log('ERGON Error: ' . $e->getMessage());
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>System Error</title></head>";
    echo "<body><h1>System Error</h1><p>Please try again later.</p>";
    echo "<a href='/ergon/login'>Return to Login</a></body></html>";
}
?>