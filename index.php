<?php
ob_start();

// Production error handling
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Session bootstrap — sets cookie params and calls session_start() once.
// All api/* files require_once the same file so params are always identical.
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
