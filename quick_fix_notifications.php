<?php
// Quick fix for notifications table module_name issue
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if notifications table has module_name column
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'module_name'");
    if ($stmt->rowCount() > 0) {
        // Add default value to module_name column
        $db->exec("ALTER TABLE notifications MODIFY COLUMN module_name VARCHAR(50) DEFAULT 'system'");
        echo "✅ Fixed module_name column default value<br>";
    }
    
    // Also ensure reference_type has default
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    if ($stmt->rowCount() > 0) {
        $db->exec("ALTER TABLE notifications MODIFY COLUMN reference_type VARCHAR(50) DEFAULT 'system'");
        echo "✅ Fixed reference_type column default value<br>";
    }
    
    echo "✅ Database fix completed successfully!<br>";
    echo "You can now create expenses without errors.<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Please check your database connection.<br>";
}
?>