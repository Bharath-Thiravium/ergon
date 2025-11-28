<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';

if (session_status() === PHP_SESSION_NONE) session_start();

echo "<h2>🔍 Notification Debug</h2>";

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'] ?? 1;
    
    echo "<p><strong>Current User ID:</strong> {$userId}</p>";
    
    // Check if notifications table exists
    $tables = $db->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
    echo "<p><strong>Notifications table exists:</strong> " . (count($tables) > 0 ? "Yes" : "No") . "</p>";
    
    // Check total notifications in database
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications");
    $stmt->execute();
    $totalNotifications = $stmt->fetchColumn();
    echo "<p><strong>Total notifications in database:</strong> {$totalNotifications}</p>";
    
    // Check notifications for current user
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ?");
    $stmt->execute([$userId]);
    $userNotifications = $stmt->fetchColumn();
    echo "<p><strong>Notifications for user {$userId}:</strong> {$userNotifications}</p>";
    
    // Show actual notifications
    $stmt = $db->prepare("SELECT * FROM notifications WHERE receiver_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Notifications:</h3>";
    if (empty($notifications)) {
        echo "<p>No notifications found for user {$userId}</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Category</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>{$notif['message']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['category']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the Notification model
    echo "<h3>Testing Notification Model:</h3>";
    $notificationModel = new Notification();
    $modelNotifications = $notificationModel->getForUser($userId);
    echo "<p><strong>Model returned:</strong> " . count($modelNotifications) . " notifications</p>";
    
    if (!empty($modelNotifications)) {
        echo "<p>First notification from model:</p>";
        echo "<pre>" . print_r($modelNotifications[0], true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong> " . $e->getTraceAsString() . "</p>";
}
?>