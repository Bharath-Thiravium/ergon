<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Database Connection Test</h2>";
    echo "✅ Database connected successfully<br><br>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br><br>";
        
        // Get all users
        $stmt = $db->query("SELECT id, name, email, role FROM users ORDER BY role, name");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Total Users: " . count($users) . "</h3>";
        
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Count by role
            $roles = array_count_values(array_column($users, 'role'));
            echo "<h3>Users by Role:</h3>";
            foreach ($roles as $role => $count) {
                echo "- $role: $count users<br>";
            }
        } else {
            echo "❌ No users found in the database<br>";
            echo "<h3>Creating sample users...</h3>";
            
            // Create sample users
            $sampleUsers = [
                ['name' => 'Admin User', 'email' => 'admin@ergon.com', 'role' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT)],
                ['name' => 'John Doe', 'email' => 'john@ergon.com', 'role' => 'user', 'password' => password_hash('user123', PASSWORD_DEFAULT)],
                ['name' => 'Jane Smith', 'email' => 'jane@ergon.com', 'role' => 'user', 'password' => password_hash('user123', PASSWORD_DEFAULT)]
            ];
            
            foreach ($sampleUsers as $user) {
                $stmt = $db->prepare("INSERT INTO users (name, email, role, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$user['name'], $user['email'], $user['role'], $user['password']]);
                if ($result) {
                    echo "✅ Created user: " . $user['name'] . " (" . $user['role'] . ")<br>";
                } else {
                    echo "❌ Failed to create user: " . $user['name'] . "<br>";
                }
            }
        }
        
    } else {
        echo "❌ Users table does not exist<br>";
        echo "<h3>Creating users table...</h3>";
        
        $createTable = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('owner', 'admin', 'user') DEFAULT 'user',
            department_id INT NULL,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($db->exec($createTable)) {
            echo "✅ Users table created successfully<br>";
        } else {
            echo "❌ Failed to create users table<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>