<?php
/**
 * Ergon - Employee Tracker & Task Manager
 * Main Application Entry Point
 */

// Force session cookie domain to empty BEFORE anything else runs.
// This overrides php.ini and works on all hosts including shared hosting.
ini_set('session.cookie_domain', '');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', '28800');
ini_set('session.use_strict_mode', '1');

// Error reporting - production safe
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Session configuration
require_once __DIR__ . '/app/config/session.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include autoloader and core files
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Controller.php';

// Initialize router
$router = new Router();

// Load routes
require_once __DIR__ . '/app/config/routes.php';

// Handle the request with error handling
try {
    $router->handleRequest();
} catch (Exception $e) {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        echo 'Error: ' . $e->getMessage();
    } else {
        error_log('Application error: ' . $e->getMessage());
        header('Location: /ergon/login');
        exit;
    }
}
?>
