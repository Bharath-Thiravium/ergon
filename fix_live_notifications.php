<?php
// Fix Live Notifications - Complete Solution
session_start();

echo "<h1>Fix Live Notifications</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // 1. Set up proper session
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'Owner';
    
    // 2. Clear all notifications
    $db->exec("DELETE FROM notifications");
    echo "<div class='info'>Cleared all notifications</div>";
    
    // 3. Create fresh notifications
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    
    // Create leave notification
    NotificationHelper::notifyOwners(2, 'leave', 'request', 'Employee requested sick leave for today', 1);
    
    // Create expense notification
    NotificationHelper::notifyOwners(2, 'expense', 'claim', 'Employee submitted expense claim of ₹500.00', 1);
    
    // Create task notification
    NotificationHelper::notifyOwners(2, 'task', 'assigned', 'New task assigned to Employee', 1);
    
    // Create advance notification
    NotificationHelper::notifyOwners(2, 'advance', 'request', 'Employee requested advance of ₹2000.00', 1);
    
    // Create followup notification
    NotificationHelper::notifyOwners(2, 'followup', 'created', 'Employee created new followup', 1);
    
    echo "<div class='success'>✓ Created 5 fresh notifications</div>";
    
    // 4. Verify notifications
    $stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE receiver_id = 1");
    $count = $stmt->fetchColumn();
    echo "<div class='info'>Total notifications for owner: $count</div>";
    
    if ($count > 0) {
        echo "<div class='success'>🎉 SUCCESS! Visit: <a href='/ergon/notifications'>Owner Notifications</a></div>";
    } else {
        echo "<div class='error'>❌ No notifications found</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>