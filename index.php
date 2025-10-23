<?php
// Root index.php - handle requests directly
session_start();

// Include configuration
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base path if exists
$basePath = '/ergon';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Handle login routes directly
if (empty($path) || $path === '/' || $path === '/login') {
    if (isset($_SESSION['user_id']) && ($path === '/' || $path === '/login')) {
        // Redirect to appropriate dashboard
        $role = $_SESSION['role'] ?? 'user';
        switch ($role) {
            case 'owner':
                header('Location: /ergon/owner/dashboard');
                break;
            case 'admin':
                header('Location: /ergon/admin/dashboard');
                break;
            default:
                header('Location: /ergon/user/dashboard');
                break;
        }
        exit;
    } else {
        // Show login form
        require_once __DIR__ . '/app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        exit;
    }
}

// Handle auth routes
if ($path === '/auth/login') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->login();
    exit;
}

if ($path === '/auth/logout') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->logout();
    exit;
}

// Handle all other routes through public/index.php routing
$_SERVER['REQUEST_URI'] = $request;
require_once __DIR__ . '/public/index.php';
?>