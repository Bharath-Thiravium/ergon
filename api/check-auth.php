<?php
/**
 * Auth Check API Endpoint
 * Returns authentication status for AJAX requests
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($authenticated) {
    // Check session timeout (1 hour)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        $authenticated = false;
    } else {
        $_SESSION['last_activity'] = time();
    }
}

echo json_encode([
    'authenticated' => $authenticated,
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null
]);
?>