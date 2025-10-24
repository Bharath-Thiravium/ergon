<?php
session_start();

// Debug edit form data flow
$userId = $_GET['id'] ?? 14;

echo "<h2>Debug Edit Form - User ID: $userId</h2>";

// Test User model getById method
require_once __DIR__ . '/app/models/User.php';
$userModel = new User();
$user = $userModel->getById($userId);

echo "<h3>1. User Model getById() Result:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

// Test direct database query
require_once __DIR__ . '/config/database.php';
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$directUser = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>2. Direct Database Query Result:</h3>";
echo "<pre>";
print_r($directUser);
echo "</pre>";

// Test what edit controller would receive
require_once __DIR__ . '/app/controllers/UsersController.php';
$controller = new UsersController();

echo "<h3>3. Edit Form Fields Expected:</h3>";
$expectedFields = [
    'name', 'employee_id', 'email', 'phone', 'date_of_birth', 'gender', 
    'address', 'emergency_contact', 'designation', 'joining_date', 
    'salary', 'role', 'status', 'department'
];

foreach ($expectedFields as $field) {
    $value = $user[$field] ?? 'NULL';
    echo "$field: $value<br>";
}

echo "<h3>4. Test Edit Link:</h3>";
echo "<a href='/ergon/users/edit/$userId'>Edit User $userId</a>";
?>