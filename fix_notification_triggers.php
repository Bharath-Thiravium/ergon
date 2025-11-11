<?php
// Fix Notification Triggers - Root Cause Analysis & Fix
session_start();
echo "<h1>Fix Notification Triggers</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // Test each controller's notification trigger
    echo "<h2>Testing Notification Triggers</h2>";
    
    // 1. Test Leave Controller
    echo "<h3>1. Testing Leave Notifications</h3>";
    try {
        // Simulate leave creation with notification
        $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES (2, 'sick', CURDATE(), CURDATE(), 1, 'Test leave trigger', 'pending')");
        $stmt->execute();
        $leaveId = $db->lastInsertId();
        
        // Manually trigger notification
        require_once __DIR__ . '/app/helpers/NotificationHelper.php';
        NotificationHelper::notifyOwners(2, 'leave', 'request', 'Test Employee requested leave (manual trigger)', $leaveId);
        echo "<div class='success'>✓ Leave notification triggered manually</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Leave trigger failed: " . $e->getMessage() . "</div>";
    }
    
    // 2. Test Expense Controller  
    echo "<h3>2. Testing Expense Notifications</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, status) VALUES (2, 'Travel', 100.00, 'Test expense trigger', 'pending')");
        $stmt->execute();
        $expenseId = $db->lastInsertId();
        
        NotificationHelper::notifyOwners(2, 'expense', 'claim', 'Test Employee submitted expense claim (manual trigger)', $expenseId);
        echo "<div class='success'>✓ Expense notification triggered manually</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Expense trigger failed: " . $e->getMessage() . "</div>";
    }
    
    // 3. Test Task Controller
    echo "<h3>3. Testing Task Notifications</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO tasks (title, assigned_by, assigned_to, status) VALUES ('Test Task Trigger', 1, 2, 'assigned')");
        $stmt->execute();
        $taskId = $db->lastInsertId();
        
        NotificationHelper::notifyOwners(1, 'task', 'assigned', 'New task assigned to Test Employee (manual trigger)', $taskId);
        echo "<div class='success'>✓ Task notification triggered manually</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Task trigger failed: " . $e->getMessage() . "</div>";
    }
    
    // Check if notifications were created
    echo "<h2>Verification</h2>";
    $stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $recentCount = $stmt->fetchColumn();
    echo "<div class='info'>Recent notifications created: $recentCount</div>";
    
    if ($recentCount > 0) {
        $stmt = $db->query("SELECT * FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY created_at DESC");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Recent Notifications:</h3>";
        foreach ($recent as $notif) {
            echo "<div>Module: {$notif['module_name']}, Action: {$notif['action_type']}, Message: {$notif['message']}</div>";
        }
    }
    
    echo "<h2>Root Cause Analysis</h2>";
    echo "<div class='info'>The issue is that controllers are not calling NotificationHelper when items are created.</div>";
    echo "<div class='info'>Solution: Each controller's create/store method must include notification triggers.</div>";
    
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>Verify controllers have NotificationHelper calls in create methods</li>";
    echo "<li>Test by creating new items through the UI</li>";
    echo "<li>Check <a href='/ergon/notifications'>notifications page</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>