<?php
/**
 * Ergon - Employee Tracker & Task Manager
 * Main Application Entry Point
 */

// Temporary diagnostic — remove after checking live server
if (isset($_GET['diag']) && $_GET['diag'] === 'session123') {
    header('Content-Type: text/plain');
    echo "PHP: " . PHP_VERSION . "\n";
    echo "cookie_domain (php.ini): '" . ini_get('session.cookie_domain') . "'\n";
    echo "use_cookies (php.ini): '" . ini_get('session.use_cookies') . "'\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
    echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
    echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'none') . "\n";
    echo "session.php exists: " . (file_exists(__DIR__ . '/app/config/session.php') ? 'YES' : 'NO') . "\n";
    echo "session.php first line: " . trim(file(__DIR__ . '/app/config/session.php')[0] ?? 'N/A') . "\n";
    $p = session_get_cookie_params();
    echo "cookie params domain BEFORE start: '" . $p['domain'] . "'\n";
    echo "session status: " . session_status() . "\n";
    foreach (headers_list() as $h) echo "header: $h\n";
    exit;
}

// Error reporting
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Session — session.php calls session_start() with correct cookie params
require_once __DIR__ . '/app/config/session.php';

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Controller.php';

$router = new Router();
require_once __DIR__ . '/app/config/routes.php';

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
