<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';
require_once __DIR__ . '/app/core/Session.php';

Session::init();

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first.";
    exit;
}

$userId = $_SESSION['user_id'];
$notification = new Notification();

echo "<h2>Notification Check for User ID: {$userId}</h2>";

// Check total notifications
$db = Database::connect();
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE receiver_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetch()['total'];

echo "<p>Total notifications for user: <strong>{$total}</strong></p>";

if ($total == 0) {
    echo "<p>❌ No notifications found. Creating test notifications...</p>";
    
    // Create test notifications
    $testNotifications = [
        [
            'sender_id' => 1,
            'receiver_id' => $userId,
            'type' => 'info',
            'category' => 'system',
            'title' => 'Welcome to Ergon',
            'message' => 'Welcome to the Ergon system! This is your first notification.',
            'reference_type' => null,
            'reference_id' => null
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $userId,
            'type' => 'success',
            'category' => 'approval',
            'title' => 'Task Assignment',
            'message' => 'You have been assigned a new task. Please check your task list.',
            'reference_type' => 'task',
            'reference_id' => 1
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $userId,
            'type' => 'warning',
            'category' => 'reminder',
            'title' => 'Leave Request Reminder',
            'message' => 'Don\'t forget to submit your leave request for next month.',
            'reference_type' => 'leave',
            'reference_id' => null
        ]
    ];
    
    foreach ($testNotifications as $testData) {
        $created = $notification->create($testData);
        if ($created) {
            echo "<p>✅ Created: {$testData['title']}</p>";
        } else {
            echo "<p>❌ Failed to create: {$testData['title']}</p>";
        }
    }
    
    // Recheck count
    $stmt->execute([$userId]);
    $newTotal = $stmt->fetch()['total'];
    echo "<p>New total notifications: <strong>{$newTotal}</strong></p>";
}

// Show recent notifications
$notifications = $notification->getForUser($userId, 20);
echo "<h3>All Notifications ({$total}):</h3>";

if (!empty($notifications)) {
    echo "<table border='1' style='width:100%; border-collapse:collapse;'>";
    echo "<tr style='background:#f5f5f5;'><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        $readStatus = $notif['is_read'] ? '✅ Yes' : '❌ No';
        $bgColor = $notif['is_read'] ? '#f9f9f9' : '#fff3cd';
        echo "<tr style='background:{$bgColor};'>";
        echo "<td>{$notif['id']}</td>";
        echo "<td><strong>{$notif['title']}</strong></td>";
        echo "<td>" . substr($notif['message'], 0, 60) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$readStatus}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No notifications to display.</p>";
}

$unreadCount = $notification->getUnreadCount($userId);
echo "<p>Unread count: <strong>{$unreadCount}</strong></p>";

echo "<hr>";
echo "<p><a href='/ergon/notifications'>Go to Notifications Page</a></p>";
echo "<p><a href='/ergon/dashboard'>Go to Dashboard</a></p>";
?>