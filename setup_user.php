<?php
/**
 * Setup User Script
 * Creates admin user for testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ERGON User Setup</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connected<br>";
    
    // Check if users table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo "Creating users table...<br>";
        $createTable = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(20) UNIQUE,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('owner', 'admin', 'user') DEFAULT 'user',
            phone VARCHAR(15),
            department VARCHAR(50),
            status ENUM('active', 'inactive') DEFAULT 'active',
            is_first_login BOOLEAN DEFAULT TRUE,
            password_reset_required BOOLEAN DEFAULT FALSE,
            temp_password VARCHAR(50),
            last_login TIMESTAMP NULL,
            last_ip VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->exec($createTable);
        echo "✅ Users table created<br>";
    } else {
        echo "✅ Users table exists<br>";
    }
    
    // Check if admin user exists
    $email = 'info@athenas.co.in';
    $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Status: " . $user['status'] . "<br>";
        
        // Update password
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, status = 'active' WHERE email = ?");
        if ($updateStmt->execute([$hashedPassword, $email])) {
            echo "✅ Password updated successfully<br>";
        } else {
            echo "❌ Failed to update password<br>";
        }
    } else {
        echo "Creating admin user...<br>";
        
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $insertStmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, status, is_first_login, password_reset_required) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($insertStmt->execute([
            'Admin User',
            $email,
            $hashedPassword,
            'admin',
            'active',
            false,
            false
        ])) {
            echo "✅ Admin user created successfully<br>";
            echo "Email: $email<br>";
            echo "Password: admin123<br>";
        } else {
            echo "❌ Failed to create admin user<br>";
        }
    }
    
    // Test authentication
    echo "<h2>Testing Authentication</h2>";
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    
    $testUser = $userModel->authenticate($email, 'admin123');
    if ($testUser) {
        echo "✅ Authentication test successful<br>";
        echo "User data: " . json_encode($testUser, JSON_PRETTY_PRINT) . "<br>";
    } else {
        echo "❌ Authentication test failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/ergon/login'>Go to Login Page</a>";
?>