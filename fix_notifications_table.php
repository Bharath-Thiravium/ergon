<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if notifications table exists and get its structure
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        // Check if module_name column exists
        $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'module_name'");
        if ($stmt->rowCount() > 0) {
            // Add default value to existing module_name column
            $db->exec("ALTER TABLE notifications MODIFY COLUMN module_name VARCHAR(50) DEFAULT 'system'");
            echo "✅ Fixed module_name column with default value\n";
        } else {
            echo "ℹ️ No module_name column found in notifications table\n";
        }
        
        // Ensure the table has the correct structure
        $db->exec("ALTER TABLE notifications 
                   ADD COLUMN IF NOT EXISTS reference_type VARCHAR(50) DEFAULT NULL,
                   ADD COLUMN IF NOT EXISTS category ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system'");
        echo "✅ Ensured correct table structure\n";
    } else {
        echo "❌ Notifications table does not exist\n";
    }
    
    // Drop and recreate the table with correct structure
    $db->exec("DROP TABLE IF EXISTS notifications_backup");
    $db->exec("CREATE TABLE notifications_backup AS SELECT * FROM notifications");
    
    $db->exec("DROP TABLE notifications");
    
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'urgent') DEFAULT 'info',
        category ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        action_url VARCHAR(500) DEFAULT NULL,
        action_text VARCHAR(100) DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        metadata JSON DEFAULT NULL,
        priority TINYINT(1) DEFAULT 1,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL DEFAULT NULL,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_receiver_unread (receiver_id, is_read, created_at),
        INDEX idx_receiver_priority (receiver_id, priority, created_at),
        INDEX idx_category_type (category, type),
        INDEX idx_reference (reference_type, reference_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✅ Recreated notifications table with correct structure\n";
    
    // Restore data if backup exists
    try {
        $db->exec("INSERT INTO notifications (sender_id, receiver_id, title, message, category, reference_type, reference_id, is_read, created_at)
                   SELECT sender_id, receiver_id, title, message, 
                          COALESCE(category, 'system'), 
                          COALESCE(reference_type, 'system'), 
                          reference_id, is_read, created_at 
                   FROM notifications_backup");
        echo "✅ Restored notification data\n";
    } catch (Exception $e) {
        echo "⚠️ Could not restore data: " . $e->getMessage() . "\n";
    }
    
    echo "✅ Notifications table fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>