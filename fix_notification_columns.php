<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Drop the problematic trigger
    $db->exec("DROP TRIGGER IF EXISTS leave_notification_insert");
    
    // Create new trigger with proper user ID lookup
    $db->exec("
        CREATE TRIGGER leave_notification_insert 
        AFTER INSERT ON leaves 
        FOR EACH ROW 
        BEGIN
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_name, action_url)
            SELECT NEW.user_id, u.id, 'info', 'approval',
                   'New Leave Request', 
                   CONCAT((SELECT name FROM users WHERE id = NEW.user_id), ' has submitted a new leave request.'),
                   'leave', NEW.id, 'leave'
            FROM users u 
            WHERE u.role IN ('admin', 'owner') AND u.status = 'active';
        END
    ");
    
    echo "Fixed leave notification trigger";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>