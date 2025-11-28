<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Drop all notification triggers that might be causing issues
    $db->exec("DROP TRIGGER IF EXISTS leave_notification_insert");
    $db->exec("DROP TRIGGER IF EXISTS leave_notification_update");
    $db->exec("DROP TRIGGER IF EXISTS expense_notification_insert");
    $db->exec("DROP TRIGGER IF EXISTS expense_notification_update");
    
    echo "Dropped all notification triggers - leave creation should work now";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>