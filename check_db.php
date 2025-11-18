<?php
echo "<h2>Database Check</h2>";

// Direct database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ergon_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to database<br><br>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Users table doesn't exist. Creating it...<br>";
        
        $createTable = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('owner', 'admin', 'user') DEFAULT 'user',
            department_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createTable);
        echo "✅ Users table created<br>";
    }
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    echo "Total users: $userCount<br>";
    
    if ($userCount == 0) {
        echo "<br>Creating users...<br>";
        
        $users = [
            ['John Doe', 'john@ergon.com', 'user', 'password123'],
            ['Jane Smith', 'jane@ergon.com', 'user', 'password123'],
            ['Admin User', 'admin@ergon.com', 'admin', 'password123']
        ];
        
        foreach ($users as $user) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user[0], $user[1], $user[2], password_hash($user[3], PASSWORD_DEFAULT)]);
            echo "✅ Created: {$user[0]} ({$user[2]})<br>";
        }
        
        echo "<br><strong>Login with:</strong><br>";
        echo "- admin@ergon.com / password123<br>";
        echo "- john@ergon.com / password123<br>";
    } else {
        echo "<br>Existing users:<br>";
        $stmt = $pdo->query("SELECT name, email, role FROM users");
        while ($user = $stmt->fetch()) {
            echo "- {$user['name']} ({$user['email']}) - {$user['role']}<br>";
        }
    }
    
    echo "<br><a href='/ergon/attendance' style='background:#3b82f6;color:white;padding:10px;text-decoration:none;border-radius:5px;'>Go to Attendance</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>