<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing edit functionality...\n";

try {
    // Test database connection
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Database connected\n";
    
    // Test user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = 13");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User 13 found: " . $user['name'] . "\n";
    } else {
        echo "❌ User 13 not found\n";
    }
    
    // Test Controller class
    require_once __DIR__ . '/app/core/Controller.php';
    echo "✅ Controller class loaded\n";
    
    // Test User model
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    echo "✅ User model loaded\n";
    
    // Test getting user by ID
    $userData = $userModel->getById(13);
    if ($userData) {
        echo "✅ User model getById works\n";
    } else {
        echo "❌ User model getById failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>