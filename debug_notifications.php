<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== NOTIFICATIONS TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    
    echo "\n=== SAMPLE NOTIFICATIONS ===\n";
    $stmt = $db->query("SELECT id, title, message, reference_type, reference_id, action_url, sender_id, receiver_id, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "No notifications found.\n";
        
        // Check if there's an old notifications table
        echo "\n=== CHECKING FOR OLD NOTIFICATIONS TABLE ===\n";
        try {
            $stmt = $db->query("SHOW TABLES LIKE '%notification%'");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            print_r($tables);
        } catch (Exception $e) {
            echo "Error checking tables: " . $e->getMessage() . "\n";
        }
    } else {
        foreach ($notifications as $notification) {
            echo "ID: {$notification['id']}\n";
            echo "Title: {$notification['title']}\n";
            echo "Message: {$notification['message']}\n";
            echo "Reference Type: {$notification['reference_type']}\n";
            echo "Reference ID: {$notification['reference_id']}\n";
            echo "Action URL: {$notification['action_url']}\n";
            echo "Read: " . ($notification['is_read'] ? 'Yes' : 'No') . "\n";
            echo "Created: {$notification['created_at']}\n";
            echo "---\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>