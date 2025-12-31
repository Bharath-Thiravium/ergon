<?php
/**
 * Quick fix for notification table missing reference_type column
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Checking notifications table structure...\n";
    
    // Check if reference_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adding missing reference_type column...\n";
        $db->exec("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) DEFAULT NULL");
        echo "Column added successfully!\n";
    } else {
        echo "reference_type column already exists.\n";
    }
    
    // Check if reference_id column exists
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adding missing reference_id column...\n";
        $db->exec("ALTER TABLE notifications ADD COLUMN reference_id INT DEFAULT NULL");
        echo "Column added successfully!\n";
    } else {
        echo "reference_id column already exists.\n";
    }
    
    echo "Notification table fix completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
