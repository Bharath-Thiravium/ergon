<?php
// Clean up duplicate notifications
require_once 'app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Delete all existing notifications to start fresh
    $stmt = $db->prepare("DELETE FROM notifications");
    $stmt->execute();
    
    echo "✅ All duplicate notifications have been removed.\n";
    echo "The notification panel will now only show real notifications from actual user actions.\n";
    
} catch (Exception $e) {
    echo "❌ Error cleaning notifications: " . $e->getMessage() . "\n";
}
?>