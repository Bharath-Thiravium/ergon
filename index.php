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

// Session cookie params MUST be set before session_start()
// ob_start() above ensures no output has been sent yet
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

$_cookieDomain = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($_cookieDomain, ':') !== false) {
    $_cookieDomain = explode(':', $_cookieDomain)[0];
}

ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 28800);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_cookieDomain,
    'secure'   => $_isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

unset($_isHttps, $_cookieDomain);

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
