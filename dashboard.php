<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /ergon/login.php');
    exit;
}

// Redirect to appropriate dashboard based on role
$role = $_SESSION['role'] ?? 'user';
switch ($role) {
    case 'owner':
        require_once __DIR__ . '/app/controllers/OwnerController.php';
        $controller = new OwnerController();
        $controller->dashboard();
        break;
    case 'admin':
        require_once __DIR__ . '/app/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
        break;
    default:
        require_once __DIR__ . '/app/controllers/UserController.php';
        $controller = new UserController();
        $controller->dashboard();
        break;
}
?>