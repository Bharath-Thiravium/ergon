<?php
session_start();

// Debug: Check session state
if (!isset($_SESSION['user_id'])) {
    // No session - redirect to login
    header('Location: /ergon/login');
    exit;
}

// Redirect to role-based dashboard
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
?>