<?php
session_start();
require_once __DIR__ . '/app/models/User.php';

// Set up session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';
$_SESSION['user_name'] = 'Owner';

echo "<h1>User Edit Debug</h1>";

try {
    $userModel = new User();
    
    // Test getting user by ID 2
    echo "<h2>Testing User ID 2:</h2>";
    $user = $userModel->getById(2);
    
    if ($user) {
        echo "<h3>User Data Found:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        // Check which fields are missing
        $expectedFields = [
            'id', 'employee_id', 'name', 'email', 'phone', 'date_of_birth', 
            'gender', 'address', 'emergency_contact', 'designation', 
            'joining_date', 'salary', 'role', 'status'
        ];
        
        echo "<h3>Field Check:</h3>";
        foreach ($expectedFields as $field) {
            $status = isset($user[$field]) ? '✅' : '❌';
            $value = $user[$field] ?? 'NULL';
            echo "$status $field: $value<br>";
        }
    } else {
        echo "<h3>❌ User not found</h3>";
        
        // Check if user exists at all
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT * FROM users WHERE id = 2");
        $directUser = $stmt->fetch();
        
        if ($directUser) {
            echo "<h3>Direct DB Query Result:</h3>";
            echo "<pre>" . print_r($directUser, true) . "</pre>";
        } else {
            echo "<h3>User ID 2 does not exist in database</h3>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>