<?php
/**
 * Production Setup Script for Hostinger
 * Run this ONCE after uploading to production
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/app/helpers/Security.php';

// Only allow in production
if (Environment::isDevelopment()) {
    die('This script only runs in production environment.');
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<!DOCTYPE html><html><head><title>ERGON Production Setup</title>";
    echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;}</style></head><body>";
    echo "<h1>🚀 ERGON Production Setup</h1>";
    
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
    echo "<p class='success'>✅ Users table created</p>";
    
    // Create activity_logs table
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type ENUM('login','logout','task_update','break_start','break_end','system_ping') DEFAULT 'system_ping',
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_activity (user_id, created_at),
        INDEX idx_activity_type (activity_type)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✅ Activity logs table created</p>";
    
    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100) DEFAULT 'ERGON Company',
        attendance_radius INT DEFAULT 200,
        backup_email VARCHAR(100),
        base_location_lat DECIMAL(10, 8) DEFAULT 0,
        base_location_lng DECIMAL(11, 8) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p class='success'>✅ Settings table created</p>";
    
    // Create default owner account
    $ownerExists = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
    
    if ($ownerExists == 0) {
        $ownerPassword = 'owner123';
        $hashedPassword = Security::hashPassword($ownerPassword);
        
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, temp_password, is_first_login, password_reset_required) 
            VALUES (?, ?, ?, 'owner', ?, FALSE, FALSE)
        ");
        
        $stmt->execute([
            'System Owner',
            'owner@company.com',
            $hashedPassword,
            $ownerPassword
        ]);
        
        echo "<p class='success'>✅ Owner account created</p>";
        echo "<div style='background:#e7f3ff;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>🔑 Login Credentials:</h3>";
        echo "<p><strong>Email:</strong> owner@company.com</p>";
        echo "<p><strong>Password:</strong> owner123</p>";
        echo "</div>";
    } else {
        echo "<p class='success'>✅ Owner account already exists</p>";
    }
    
    // Insert default settings
    $settingsExists = $conn->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($settingsExists == 0) {
        $conn->exec("INSERT INTO settings (company_name) VALUES ('ERGON Company')");
        echo "<p class='success'>✅ Default settings created</p>";
    }
    
    echo "<br><h2>🎉 Production Setup Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this setup file for security</li>";
    echo "<li>Access your application at: <a href='/login.php'>Login Page</a></li>";
    echo "<li>Login with owner credentials above</li>";
    echo "</ol>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>