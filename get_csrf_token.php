<?php
session_start();

// Set up session like the real application
$_SESSION['user_id'] = 16; // From your task data

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token'],
    'user_id' => $_SESSION['user_id']
]);
?>