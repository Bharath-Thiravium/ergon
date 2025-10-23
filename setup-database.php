<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Security.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>🛠️ Database Setup</h1>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(20) UNIQUE,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('owner', 'admin', 'user') DEFAULT 'user',
        phone VARCHAR(20),
        department VARCHAR(100),
        temp_password VARCHAR(50),
        is_first_login BOOLEAN DEFAULT TRUE,
        password_reset_required BOOLEAN DEFAULT FALSE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        last_ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p>✅ Users table created</p>";
    
    // Check if users exist
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Create default users
        $users = [
            ['name' => 'System Owner', 'email' => 'owner@company.com', 'role' => 'owner', 'password' => 'owner123'],
            ['name' => 'System Admin', 'email' => 'admin@company.com', 'role' => 'admin', 'password' => 'admin123'],
            ['name' => 'Test User', 'email' => 'user@company.com', 'role' => 'user', 'password' => 'user123'],
            ['name' => 'ilayaraja', 'email' => 'ilayaraja@company.com', 'role' => 'user', 'password' => 'user123', 'department' => 'IT Department']
        ];
        
        foreach ($users as $user) {
            $hashedPassword = Security::hashPassword($user['password']);
            
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, department, temp_password, is_first_login, password_reset_required) 
                VALUES (?, ?, ?, ?, ?, ?, FALSE, FALSE)
            ");
            
            $stmt->execute([
                $user['name'],
                $user['email'],
                $hashedPassword,
                $user['role'],
                $user['department'] ?? null,
                $user['password']
            ]);
            
            echo "<p>✅ Created user: {$user['email']} / {$user['password']}</p>";
        }
    } else {
        echo "<p>✅ Found {$result['count']} existing users</p>";
    }
    
    // Show all users
    echo "<h2>Current Users:</h2>";
    $stmt = $conn->query("SELECT name, email, role, temp_password FROM users ORDER BY role DESC");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Role</th><th>Password</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td style='background: yellow;'>{$user['temp_password']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>✅ Setup Complete!</h3>";
    echo "<p><a href='/ergon/'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>