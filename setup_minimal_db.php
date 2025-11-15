<?php
// Minimal database setup script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setting up Minimal Database</h1>";

try {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'ergon_db';
    
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "✅ Database created/verified<br>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin', 'owner') DEFAULT 'user',
            department_id INT NULL,
            employee_id VARCHAR(50) NULL,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Users table created/verified<br>";
    
    // Create departments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Departments table created/verified<br>";
    
    // Check if we have any users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Create a default admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (name, email, password, role) 
            VALUES ('Admin User', 'admin@ergon.com', '$hashedPassword', 'owner')
        ");
        echo "✅ Default admin user created (admin@ergon.com / admin123)<br>";
    }
    
    echo "<br><strong>Setup Complete!</strong><br>";
    echo "<a href='/ergon/login'>Go to Login</a> | ";
    echo "<a href='/ergon/attendance'>Test Attendance</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>