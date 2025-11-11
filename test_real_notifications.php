<?php
// Test Real Notification Creation
session_start();
$_SESSION['user_id'] = 2; // Employee
$_SESSION['role'] = 'user';

echo "<h1>Test Real Notification Creation</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    $db = Database::connect();
    
    // Clear old notifications
    $db->exec("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    echo "<div class='success'>✓ Cleared old notifications</div>";
    
    // Create new task and trigger notification
    $stmt = $db->prepare("INSERT INTO tasks (title, assigned_by, assigned_to, status) VALUES ('NEW REAL TASK', 2, 1, 'assigned')");
    $stmt->execute();
    $taskId = $db->lastInsertId();
    
    // Manually trigger notification (this is what controllers should do)
    NotificationHelper::notifyOwners(2, 'task', 'assigned', 'Employee created NEW REAL TASK for owner', $taskId);
    
    echo "<div class='success'>✓ Created new task with notification</div>";
    
    // Check if notification was created
    $stmt = $db->query("SELECT * FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY created_at DESC");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Recent Notifications (last 5 minutes):</h2>";
    foreach ($recent as $notif) {
        echo "<div>{$notif['module_name']} - {$notif['message']} - {$notif['created_at']}</div>";
    }
    
    echo "<p><a href='/ergon/notifications'>Check Owner Notifications</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>