<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "Adding missing columns to notifications table...\n";
    
    // Add sender_id column
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `sender_id` int DEFAULT NULL AFTER `id`");
    echo "Added sender_id column\n";
    
    // Add receiver_id column  
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `receiver_id` int DEFAULT NULL AFTER `sender_id`");
    echo "Added receiver_id column\n";
    
    // Add module_name column
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `module_name` varchar(50) DEFAULT NULL AFTER `message`");
    echo "Added module_name column\n";
    
    // Add action_type column
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `action_type` varchar(50) DEFAULT NULL AFTER `module_name`");
    echo "Added action_type column\n";
    
    // Update existing records
    $pdo->exec("UPDATE `notifications` SET `receiver_id` = `user_id` WHERE `receiver_id` IS NULL");
    echo "Updated existing records\n";
    
    echo "Database fix completed successfully!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>