<?php
/**
 * Database Table Fix Script
 * Creates missing tables that are causing 500 errors
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Check and create activity_logs table
    $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() == 0) {
        echo "Creating activity_logs table...\n";
        $sql = "CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        )";
        $db->exec($sql);
        echo "activity_logs table created.\n";
    } else {
        echo "activity_logs table already exists.\n";
    }
    
    // Check and create settings table
    $stmt = $db->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        echo "Creating settings table...\n";
        $sql = "CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) DEFAULT 'ERGON',
            company_email VARCHAR(255),
            company_phone VARCHAR(50),
            company_address TEXT,
            working_hours_start TIME DEFAULT '09:00:00',
            working_hours_end TIME DEFAULT '18:00:00',
            timezone VARCHAR(50) DEFAULT 'UTC',
            office_latitude DECIMAL(10, 8) DEFAULT 0,
            office_longitude DECIMAL(11, 8) DEFAULT 0,
            office_address TEXT,
            attendance_radius INT DEFAULT 200,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        
        // Insert default settings
        $db->exec("INSERT INTO settings (company_name) VALUES ('ERGON')");
        echo "settings table created with default values.\n";
    } else {
        echo "settings table already exists.\n";
    }
    
    // Check and create followups table
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    if ($stmt->rowCount() == 0) {
        echo "Creating followups table...\n";
        $sql = "CREATE TABLE followups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assigned_to INT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            follow_date DATE NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('pending', 'completed', 'rescheduled') DEFAULT 'pending',
            task_id INT,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_follow_date (follow_date),
            INDEX idx_status (status)
        )";
        $db->exec($sql);
        echo "followups table created.\n";
    } else {
        echo "followups table already exists.\n";
    }
    
    // Check if advances table exists and has correct structure
    $stmt = $db->query("SHOW TABLES LIKE 'advances'");
    if ($stmt->rowCount() == 0) {
        echo "Creating advances table...\n";
        $sql = "CREATE TABLE advances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            reason TEXT NOT NULL,
            requested_date DATE NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by INT,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        )";
        $db->exec($sql);
        echo "advances table created.\n";
    } else {
        echo "advances table already exists.\n";
    }
    
    // Check attendance table structure
    $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
    if ($stmt->rowCount() == 0) {
        echo "Creating attendance table...\n";
        $sql = "CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            clock_in TIMESTAMP NULL,
            clock_out TIMESTAMP NULL,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_clock_in (clock_in)
        )";
        $db->exec($sql);
        echo "attendance table created.\n";
    } else {
        echo "attendance table already exists.\n";
    }
    
    echo "\nDatabase table check completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>