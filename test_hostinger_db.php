<?php
// Test Hostinger database connection and user data

try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Hostinger Database Test</h2>";
    
    // Test connection
    echo "<p>✅ Database connection successful</p>";
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check all users
    echo "<h3>All Users:</h3>";
    $stmt = $conn->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    // Test specific user
    echo "<h3>User ID 2:</h3>";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([2]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>