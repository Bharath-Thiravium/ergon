<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Populating Notification Reference IDs</h2>";
    
    // Get notifications without reference_id
    $stmt = $db->query("
        SELECT id, reference_type, message, sender_id, created_at 
        FROM notifications 
        WHERE reference_id IS NULL 
        AND reference_type IN ('expense', 'leave', 'advance')
        ORDER BY created_at DESC
    ");
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found " . count($notifications) . " notifications to fix</p>";
    
    $fixed = 0;
    
    foreach ($notifications as $notif) {
        $referenceId = null;
        
        switch ($notif['reference_type']) {
            case 'expense':
                // Find expense by sender and approximate time
                $stmt = $db->prepare("
                    SELECT id FROM expenses 
                    WHERE user_id = ? 
                    AND ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) <= 5
                    ORDER BY ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) ASC
                    LIMIT 1
                ");
                $stmt->execute([$notif['sender_id'], $notif['created_at'], $notif['created_at']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) $referenceId = $result['id'];
                break;
                
            case 'leave':
                $stmt = $db->prepare("
                    SELECT id FROM leaves 
                    WHERE user_id = ? 
                    AND ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) <= 5
                    ORDER BY ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) ASC
                    LIMIT 1
                ");
                $stmt->execute([$notif['sender_id'], $notif['created_at'], $notif['created_at']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) $referenceId = $result['id'];
                break;
                
            case 'advance':
                $stmt = $db->prepare("
                    SELECT id FROM advances 
                    WHERE user_id = ? 
                    AND ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) <= 5
                    ORDER BY ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) ASC
                    LIMIT 1
                ");
                $stmt->execute([$notif['sender_id'], $notif['created_at'], $notif['created_at']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) $referenceId = $result['id'];
                break;
        }
        
        if ($referenceId) {
            $updateStmt = $db->prepare("UPDATE notifications SET reference_id = ? WHERE id = ?");
            $updateStmt->execute([$referenceId, $notif['id']]);
            echo "<p>✅ Fixed {$notif['reference_type']} notification ID {$notif['id']} → reference_id = {$referenceId}</p>";
            $fixed++;
        } else {
            echo "<p>❌ No matching {$notif['reference_type']} found for notification ID {$notif['id']}</p>";
        }
    }
    
    echo "<h3>✅ Fixed {$fixed} notifications</h3>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>