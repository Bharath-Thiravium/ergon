<?php
// Debug Notification Display Issue
session_start();

echo "<h1>Debug Notification Display</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    echo "<h2>Current Session</h2>";
    echo "<div class='info'>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</div>";
    echo "<div class='info'>Role: " . ($_SESSION['role'] ?? 'Not set') . "</div>";
    
    echo "<h2>Database Check</h2>";
    
    // Check notifications table
    $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'>Total notifications in DB: " . count($notifications) . "</div>";
    
    foreach ($notifications as $notif) {
        echo "<div>ID: {$notif['id']}, Sender: {$notif['sender_id']}, Receiver: {$notif['receiver_id']}, Message: {$notif['message']}</div>";
    }
    
    // Test notification model directly
    echo "<h2>Test Notification Model</h2>";
    require_once __DIR__ . '/app/models/Notification.php';
    $notificationModel = new Notification();
    
    // Create test notification
    $result = $notificationModel->create([
        'sender_id' => 2,
        'receiver_id' => 1,
        'module_name' => 'test',
        'action_type' => 'debug',
        'message' => 'Debug test notification - ' . date('Y-m-d H:i:s'),
        'reference_id' => 999
    ]);
    
    if ($result) {
        echo "<div class='success'>✓ Test notification created</div>";
    } else {
        echo "<div class='error'>✗ Failed to create test notification</div>";
    }
    
    // Get notifications for user ID 1
    $userNotifications = $notificationModel->getForUser(1);
    echo "<div class='info'>Notifications for user 1: " . count($userNotifications) . "</div>";
    
    foreach ($userNotifications as $notif) {
        echo "<div>- {$notif['module_name']}: {$notif['message']} ({$notif['created_at']})</div>";
    }
    
    // Set session to user 1 and test controller
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    
    echo "<h2>Test Controller</h2>";
    require_once __DIR__ . '/app/controllers/NotificationController.php';
    
    // Simulate controller call
    ob_start();
    $controller = new NotificationController();
    
    // Capture any output
    $output = ob_get_clean();
    
    echo "<div class='success'>✓ Controller loaded successfully</div>";
    
    echo "<h2>Final Check</h2>";
    echo "<p><a href='/ergon/notifications'>Visit Notifications Page</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace: " . $e->getTraceAsString() . "</div>";
}
?>