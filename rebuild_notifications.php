<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/NotificationHelper.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔄 Rebuilding Notifications System</h2>";
    
    // 1. Clear all existing notifications
    echo "<h3>Step 1: Clearing existing notifications</h3>";
    $stmt = $db->query("DELETE FROM notifications");
    $deleted = $stmt->rowCount();
    echo "<p>✅ Deleted {$deleted} old notifications</p>";
    
    // 2. Get all expenses and create notifications
    echo "<h3>Step 2: Creating expense notifications</h3>";
    $stmt = $db->query("SELECT e.*, u.name FROM expenses e JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expenses as $expense) {
        NotificationHelper::notifyExpenseClaim(
            $expense['user_id'], 
            $expense['name'], 
            $expense['amount'], 
            $expense['id']
        );
        echo "<p>✅ Created expense notification for {$expense['name']} - ₹{$expense['amount']} (ID: {$expense['id']})</p>";
    }
    
    // 3. Get all leaves and create notifications
    echo "<h3>Step 3: Creating leave notifications</h3>";
    $stmt = $db->query("SELECT l.*, u.name FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($leaves as $leave) {
        NotificationHelper::notifyLeaveRequest(
            $leave['user_id'], 
            $leave['name'], 
            $leave['id']
        );
        echo "<p>✅ Created leave notification for {$leave['name']} - {$leave['leave_type']} (ID: {$leave['id']})</p>";
    }
    
    // 4. Get all advances and create notifications
    echo "<h3>Step 4: Creating advance notifications</h3>";
    $stmt = $db->query("SELECT a.*, u.name FROM advances a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
    $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($advances as $advance) {
        NotificationHelper::notifyAdvanceRequest(
            $advance['user_id'], 
            $advance['name'], 
            $advance['amount'], 
            $advance['id']
        );
        echo "<p>✅ Created advance notification for {$advance['name']} - ₹{$advance['amount']} (ID: {$advance['id']})</p>";
    }
    
    // 5. Verify results
    echo "<h3>Step 5: Verification</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total notifications created: {$total}</strong></p>";
    
    $stmt = $db->query("
        SELECT module_name, COUNT(*) as count 
        FROM notifications 
        GROUP BY module_name 
        ORDER BY count DESC
    ");
    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Module</th><th>Count</th></tr>";
    foreach ($breakdown as $row) {
        echo "<tr><td>{$row['module_name']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
    
    // 6. Show sample notifications with URLs
    echo "<h3>Step 6: Sample notifications with URLs</h3>";
    $stmt = $db->query("
        SELECT id, module_name, reference_id, message, created_at 
        FROM notifications 
        WHERE reference_id IS NOT NULL 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Module</th><th>Ref ID</th><th>Message</th><th>Generated URL</th></tr>";
    foreach ($samples as $notif) {
        $url = "/ergon/{$notif['module_name']}s/view/{$notif['reference_id']}";
        if ($notif['module_name'] === 'advance') $url = "/ergon/advances/view/{$notif['reference_id']}";
        
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['module_name']}</td>";
        echo "<td>{$notif['reference_id']}</td>";
        echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
        echo "<td><a href='{$url}' target='_blank'>{$url}</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Notification rebuild complete!</h2>";
    echo "<p><a href='/ergon/notifications'>View Notifications</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>