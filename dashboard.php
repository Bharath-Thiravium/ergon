<?php
/**
 * Direct Dashboard Access Handler
 * Forces authentication before allowing access
 */

// Start session
session_start();

// Set no-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['login_time'])) {
    header('Location: /ergon/login');
    exit;
}

// Check session timeout (1 hour)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: /ergon/login?timeout=1');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Redirect to appropriate dashboard based on role
$role = $_SESSION['role'];
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