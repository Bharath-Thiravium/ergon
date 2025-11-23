<?php
/**
 * Fix Follow-up Database Tables
 * This script creates the proper database structure for follow-ups
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "Creating follow-up database tables...\n";
    
    // Create contacts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(255),
        company VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create followups table with proper structure
    $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        contact_id INT,
        task_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        followup_type ENUM('standalone', 'task') DEFAULT 'standalone',
        follow_up_date DATE NOT NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_contact_id (contact_id),
        INDEX idx_task_id (task_id),
        INDEX idx_follow_up_date (follow_up_date),
        INDEX idx_status (status)
    )");
    
    // Create followup_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS followup_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        followup_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        old_value TEXT,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_followup_id (followup_id)
    )");
    
    echo "Database tables created successfully!\n";
    echo "Follow-up system is now ready to use.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>