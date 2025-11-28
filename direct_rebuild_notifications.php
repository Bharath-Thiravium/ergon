<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔄 Direct Notification Rebuild</h2>";
    
    // Clear existing notifications
    $db->exec("DELETE FROM notifications");
    echo "<p>✅ Cleared existing notifications</p>";
    
    // Get owners for notifications
    $stmt = $db->query("SELECT id FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($owners)) {
        echo "<p>❌ No owners found</p>";
        exit;
    }
    
    $created = 0;
    
    // Create expense notifications
    $stmt = $db->query("SELECT e.*, u.name FROM expenses e JOIN users u ON e.user_id = u.id");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expenses as $expense) {
        foreach ($owners as $ownerId) {
            $insertStmt = $db->prepare("
                INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id, is_read, created_at) 
                VALUES (?, ?, 'expense', 'approval_request', ?, ?, 0, NOW())
            ");
            $message = "{$expense['name']} submitted an expense claim of ₹{$expense['amount']} for approval";
            $insertStmt->execute([$expense['user_id'], $ownerId, $message, $expense['id']]);
            $created++;
        }
    }
    
    // Create leave notifications
    $stmt = $db->query("SELECT l.*, u.name FROM leaves l JOIN users u ON l.user_id = u.id");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($leaves as $leave) {
        foreach ($owners as $ownerId) {
            $insertStmt = $db->prepare("
                INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id, is_read, created_at) 
                VALUES (?, ?, 'leave', 'approval_request', ?, ?, 0, NOW())
            ");
            $message = "{$leave['name']} submitted a leave request for approval";
            $insertStmt->execute([$leave['user_id'], $ownerId, $message, $leave['id']]);
            $created++;
        }
    }
    
    // Create advance notifications
    $stmt = $db->query("SELECT a.*, u.name FROM advances a JOIN users u ON a.user_id = u.id");
    $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($advances as $advance) {
        foreach ($owners as $ownerId) {
            $insertStmt = $db->prepare("
                INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id, is_read, created_at) 
                VALUES (?, ?, 'advance', 'approval_request', ?, ?, 0, NOW())
            ");
            $message = "{$advance['name']} submitted a salary advance request of ₹{$advance['amount']} for approval";
            $insertStmt->execute([$advance['user_id'], $ownerId, $message, $advance['id']]);
            $created++;
        }
    }
    
    echo "<p>✅ Created {$created} notifications</p>";
    
    // Verify
    $stmt = $db->query("SELECT COUNT(*) FROM notifications");
    $total = $stmt->fetchColumn();
    echo "<p><strong>Total in database: {$total}</strong></p>";
    
    // Show samples
    $stmt = $db->query("SELECT module_name, reference_id, message FROM notifications LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample notifications:</h3>";
    foreach ($samples as $sample) {
        $url = "/ergon/{$sample['module_name']}s/view/{$sample['reference_id']}";
        if ($sample['module_name'] === 'advance') $url = "/ergon/advances/view/{$sample['reference_id']}";
        echo "<p>• {$sample['message']} → <a href='{$url}'>{$url}</a></p>";
    }
    
    echo "<p><a href='/ergon/notifications'>View Notifications</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>