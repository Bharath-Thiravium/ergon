<?php
// Simple database connection test
echo "<h2>Database Connection Test</h2>";

try {
    // Test basic PDO connection
    $host = 'localhost';
    $dbname = 'ergon_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Direct PDO connection successful<br>";
    
    // Test using the app's database class
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "✅ App database connection successful<br><br>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Count users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "📊 Total users: $count<br>";
        
        if ($count > 0) {
            // Show users by role
            $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $roles = $stmt->fetchAll();
            echo "<br>Users by role:<br>";
            foreach ($roles as $role) {
                echo "- {$role['role']}: {$role['count']}<br>";
            }
        }
    } else {
        echo "❌ Users table does not exist<br>";
    }
    
    echo "<br><a href='/ergon/fix_no_employees.php'>🔧 Run Fix Script</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>