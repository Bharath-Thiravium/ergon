<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Simple Notification Fix</h2>";
    
    // Create sample records in each table if they don't exist
    $tables = ['expenses', 'leaves', 'advances'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM {$table}");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            echo "<p>Creating sample {$table} record...</p>";
            
            switch ($table) {
                case 'expenses':
                    $db->exec("INSERT INTO expenses (user_id, category, amount, description, status, created_at) VALUES (1, 'Sample', 100.00, 'Sample expense', 'pending', NOW())");
                    break;
                case 'leaves':
                    $db->exec("INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, status, created_at) VALUES (1, 'casual', CURDATE(), CURDATE(), 'Sample leave', 'pending', NOW())");
                    break;
                case 'advances':
                    $db->exec("INSERT INTO advances (user_id, amount, reason, status, created_at) VALUES (1, 1000.00, 'Sample advance', 'pending', NOW())");
                    break;
            }
        }
    }
    
    // Now update notifications with reference IDs
    $stmt = $db->query("
        SELECT id, module_name, sender_id 
        FROM notifications 
        WHERE reference_id IS NULL 
        AND module_name IN ('expense', 'leave', 'advance')
    ");
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fixed = 0;
    
    foreach ($notifications as $notif) {
        $table = $notif['module_name'] === 'advance' ? 'advances' : $notif['module_name'] . 's';
        
        $stmt = $db->prepare("SELECT id FROM {$table} ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $updateStmt = $db->prepare("UPDATE notifications SET reference_id = ? WHERE id = ?");
            $updateStmt->execute([$result['id'], $notif['id']]);
            echo "<p>✅ Fixed notification {$notif['id']} → reference_id = {$result['id']}</p>";
            $fixed++;
        }
    }
    
    echo "<h3>✅ Fixed {$fixed} notifications</h3>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>