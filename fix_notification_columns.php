<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Fixing notifications table structure...<br><br>";
    
    // Check and add module_name column
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'module_name'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE notifications ADD COLUMN module_name VARCHAR(50) DEFAULT 'system'");
        echo "✅ Added module_name column<br>";
    } else {
        $db->exec("ALTER TABLE notifications MODIFY COLUMN module_name VARCHAR(50) DEFAULT 'system'");
        echo "✅ Fixed module_name column default<br>";
    }
    
    // Check and add action_type column
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'action_type'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE notifications ADD COLUMN action_type VARCHAR(50) DEFAULT 'info'");
        echo "✅ Added action_type column<br>";
    } else {
        $db->exec("ALTER TABLE notifications MODIFY COLUMN action_type VARCHAR(50) DEFAULT 'info'");
        echo "✅ Fixed action_type column default<br>";
    }
    
    // Check and add is_batched column
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'is_batched'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE notifications ADD COLUMN is_batched BOOLEAN DEFAULT FALSE");
        echo "✅ Added is_batched column<br>";
    }
    
    // Create notification_queue table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS notification_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_data JSON NOT NULL,
        priority INT DEFAULT 2,
        status ENUM('pending', 'processed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL
    )");
    echo "✅ Ensured notification_queue table exists<br>";
    
    echo "<br>✅ All notification table fixes completed successfully!<br>";
    echo "You can now create expenses without errors.<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>