<?php
session_start();

// Debug user edit data loading
$userId = $_GET['id'] ?? 2; // Default to user ID 2

require_once __DIR__ . '/app/models/User.php';

$userModel = new User();
$user = $userModel->getById($userId);

echo "<h2>Debug User Edit - ID: $userId</h2>";

echo "<h3>User Data:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Edit Link:</h3>";
echo "<a href='/ergon/users/edit/$userId'>Edit User $userId</a>";
?>