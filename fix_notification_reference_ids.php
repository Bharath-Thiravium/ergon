<?php
/**
 * Fix existing notifications that have NULL reference_id
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Fixing Notification Reference IDs</h2>";
    
    // Get notifications with NULL reference_id but have reference_type
    $stmt = $db->query("
        SELECT n.*, e.id as expense_id, l.id as leave_id, a.id as advance_id 
        FROM notifications n 
        LEFT JOIN expenses e ON n.message LIKE CONCAT('%', e.amount, '%') AND n.reference_type = 'expense'
        LEFT JOIN leaves l ON n.reference_type = 'leave' 
        LEFT JOIN advances a ON n.message LIKE CONCAT('%', a.amount, '%') AND n.reference_type = 'advance'
        WHERE n.reference_id IS NULL 
        AND n.reference_type IS NOT NULL 
        AND n.reference_type != 'system'
        ORDER BY n.created_at DESC
    ");
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($notifications) . " notifications to fix</p>";
    
    $fixed = 0;
    foreach ($notifications as $notif) {
        $referenceId = null;
        
        // Try to match based on type and content
        switch ($notif['reference_type']) {
            case 'expense':
                if ($notif['expense_id']) {
                    $referenceId = $notif['expense_id'];
                }
                break;
            case 'leave':
                if ($notif['leave_id']) {
                    $referenceId = $notif['leave_id'];
                }
                break;
            case 'advance':
                if ($notif['advance_id']) {
                    $referenceId = $notif['advance_id'];
                }
                break;
        }
        
        if ($referenceId) {
            $updateStmt = $db->prepare("UPDATE notifications SET reference_id = ? WHERE id = ?");
            $result = $updateStmt->execute([$referenceId, $notif['id']]);
            
            if ($result) {
                echo "<p>✅ Fixed notification ID {$notif['id']} - set reference_id to {$referenceId}</p>";
                $fixed++;
            }
        } else {
            echo "<p>⚠️ Could not determine reference_id for notification ID {$notif['id']} ({$notif['reference_type']})</p>";
        }
    }
    
    echo "<h3>Summary: Fixed {$fixed} out of " . count($notifications) . " notifications</h3>";
    
    // Show current state
    echo "<h3>Current Notifications with Reference IDs:</h3>";
    $stmt = $db->query("
        SELECT id, reference_type, reference_id, title, message, created_at 
        FROM notifications 
        WHERE reference_type IS NOT NULL 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Type</th><th>Ref ID</th><th>Title</th><th>Message</th><th>Created</th></tr>";
    foreach ($current as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['reference_type']}</td>";
        echo "<td>{$notif['reference_id']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>