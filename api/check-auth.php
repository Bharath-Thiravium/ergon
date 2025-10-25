<?php
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$authenticated = isset($_SESSION['user_id']) && isset($_SESSION['login_time']);

echo json_encode(['authenticated' => $authenticated]);
?>