<?php
// Simple index for testing
session_start();

// Basic error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, show dashboard
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
    // User not logged in, show login
    header('Location: /ergon/login');
    exit;
}
?>