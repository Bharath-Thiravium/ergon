<?php
// Test user fetching
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';

echo "=== User Fetch Test ===\n";

try {
    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Database connected\n";
    
    // Test User model
    $userModel = new User();
    echo "✅ User model created\n";
    
    // Check if user ID 2 exists
    $user = $userModel->getById(2);
    if ($user) {
        echo "✅ User ID 2 found:\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Status: " . $user['status'] . "\n";
    } else {
        echo "❌ User ID 2 not found\n";
        
        // Check all users
        echo "\nAll users in database:\n";
        $stmt = $conn->query("SELECT id, name, email, role, status FROM users");
        $users = $stmt->fetchAll();
        foreach ($users as $u) {
            echo "ID: {$u['id']}, Name: {$u['name']}, Email: {$u['email']}, Role: {$u['role']}, Status: {$u['status']}\n";
        }
    }
    
    // Test authentication
    echo "\n=== Authentication Test ===\n";
    $authResult = $userModel->authenticate('admin@athenas.co.in', 'password');
    if ($authResult) {
        echo "✅ Authentication successful\n";
        echo "User: " . json_encode($authResult) . "\n";
    } else {
        echo "❌ Authentication failed\n";
    }
    
    // Test session
    echo "\n=== Session Test ===\n";
    if (isset($_SESSION['user_id'])) {
        echo "Session user_id: " . $_SESSION['user_id'] . "\n";
        echo "Session role: " . ($_SESSION['role'] ?? 'not set') . "\n";
        echo "Session user_name: " . ($_SESSION['user_name'] ?? 'not set') . "\n";
    } else {
        echo "No active session\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>