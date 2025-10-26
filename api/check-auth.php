<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Not authenticated']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'] ?? 'user',
    'name' => $_SESSION['user_name'] ?? 'User'
]);
?>
