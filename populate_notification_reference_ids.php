<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Populating Notification Reference IDs</h2>";
    
    // Check table structure first
    $stmt = $db->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    echo "<p>Available columns: " . implode(', ', $columnNames) . "</p>";
    
    // Use module_name if reference_type doesn't exist
    $typeColumn = in_array('reference_type', $columnNames) ? 'reference_type' : 'module_name';
    $idColumn = in_array('reference_id', $columnNames) ? 'reference_id' : 'id';
    
    // Get notifications without reference_id
    $stmt = $db->query("
        SELECT id, {$typeColumn} as ref_type, message, sender_id, created_at 
        FROM notifications 
        WHERE ({$idColumn} IS NULL OR {$idColumn} = 0)
        AND {$typeColumn} IN ('expense', 'leave', 'advance')
        ORDER BY created_at DESC
    ");
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found " . count($notifications) . " notifications to fix</p>";
    
    $fixed = 0;
    
    foreach ($notifications as $notif) {
        $referenceId = null;
        
        switch ($notif['ref_type']) {
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
            $updateColumn = in_array('reference_id', $columnNames) ? 'reference_id' : 'module_id';
            $updateStmt = $db->prepare("UPDATE notifications SET {$updateColumn} = ? WHERE id = ?");
            $updateStmt->execute([$referenceId, $notif['id']]);
            echo "<p>✅ Fixed {$notif['ref_type']} notification ID {$notif['id']} → {$updateColumn} = {$referenceId}</p>";
            $fixed++;
        } else {
            echo "<p>❌ No matching {$notif['ref_type']} found for notification ID {$notif['id']}</p>";
        }
    }
    
    echo "<h3>✅ Fixed {$fixed} notifications</h3>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>