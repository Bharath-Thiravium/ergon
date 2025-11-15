<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Database Connection...<br>";

try {
    require_once __DIR__ . '/app/config/environment.php';
    echo "Environment loaded<br>";
    
    require_once __DIR__ . '/app/config/database.php';
    echo "Database config loaded<br>";
    
    $db = Database::connect();
    echo "Database connected successfully<br>";
    
    // Test query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Test query result: " . $result['test'] . "<br>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "Users table exists<br>";
    } else {
        echo "Users table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>