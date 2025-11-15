<?php
// Simple database check script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Check</h1>";

try {
    // Check if database exists
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if ergon_db exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'ergon_db'");
    if ($stmt->fetch()) {
        echo "✅ Database 'ergon_db' exists<br>";
        
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=ergon_db", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check for users table
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->fetch()) {
            echo "✅ Users table exists<br>";
            
            // Count users
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            echo "Users count: " . $result['count'] . "<br>";
            
            // Check if there's at least one user to test with
            if ($result['count'] > 0) {
                $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 1");
                $user = $stmt->fetch();
                echo "Sample user: " . $user['name'] . " (" . $user['role'] . ")<br>";
            }
        } else {
            echo "❌ Users table does not exist<br>";
        }
        
        // Check for attendance table
        $stmt = $pdo->query("SHOW TABLES LIKE 'attendance'");
        if ($stmt->fetch()) {
            echo "✅ Attendance table exists<br>";
        } else {
            echo "⚠️ Attendance table does not exist (will be created automatically)<br>";
        }
        
    } else {
        echo "❌ Database 'ergon_db' does not exist<br>";
        echo "Creating database...<br>";
        
        $pdo->exec("CREATE DATABASE ergon_db");
        echo "✅ Database 'ergon_db' created<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/ergon/attendance'>Test Attendance Page</a>";
?>