<?php
/**
 * Security setup script - Run this once to create security tables
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $conn = Database::connect();
    
    echo "Setting up security tables...\n";
    
    // Add security columns to users table
    $queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0",
        
        // Create login attempts table
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            INDEX idx_email_time (email, attempted_at),
            INDEX idx_ip_time (ip_address, attempted_at)
        )",
        
        // Create rate limiting table
        "CREATE TABLE IF NOT EXISTS rate_limit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL DEFAULT 'login',
            attempted_at DATETIME NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL,
            INDEX idx_identifier_action_time (identifier, action, attempted_at),
            INDEX idx_ip_time (ip_address, attempted_at)
        )",
        
        // Create password change log table
        "CREATE TABLE IF NOT EXISTS password_change_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            changed_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_time (user_id, changed_at)
        )"
    ];
    
    foreach ($queries as $query) {
        try {
            $conn->exec($query);
            echo "✓ Executed: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Column already exists: " . substr($query, 0, 50) . "...\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nSecurity setup completed successfully!\n";
    echo "Next steps:\n";
    echo "1. Edit .env file with your SMTP settings\n";
    echo "2. Test the security features\n";
    
} catch (Exception $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
}
?>