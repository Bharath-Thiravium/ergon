<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';

echo "<h1>User Fetch Debug</h1>";

try {
    // Direct database check
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Direct Database Query:</h2>";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = 2");
    $stmt->execute();
    $directResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($directResult) {
        echo "✅ User found in database<br>";
        echo "<pre>" . print_r($directResult, true) . "</pre>";
    } else {
        echo "❌ User not found in database<br>";
    }
    
    // User model check
    echo "<h2>User Model Query:</h2>";
    $userModel = new User();
    $modelResult = $userModel->getById(2);
    
    if ($modelResult) {
        echo "✅ User found via model<br>";
        echo "<pre>" . print_r($modelResult, true) . "</pre>";
    } else {
        echo "❌ User not found via model<br>";
    }
    
    // Check if columns exist
    echo "<h2>Table Structure:</h2>";
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>