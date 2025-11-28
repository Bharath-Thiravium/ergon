<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/NotificationHelper.php';
require_once __DIR__ . '/app/models/Notification.php';

try {
    $db = Database::connect();
    
    echo "<h2>Testing Notification Fix</h2>";
    
    // Ensure we have test users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'owner'");
    $ownerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($ownerCount == 0) {
        echo "<p>Creating test owner...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Owner', 'owner@test.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
        echo "<p>✅ Test owner created</p>";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($userCount == 0) {
        echo "<p>Creating test user...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test User', 'user@test.com', ?, 'user', 'active')");
        $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
        echo "<p>✅ Test user created</p>";
    }
    
    // Get test user
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Clear old test notifications
    $db->exec("DELETE FROM notifications WHERE message LIKE '%TEST NOTIFICATION%'");
    
    // Test notification creation
    echo "<h3>Creating Test Notifications</h3>";
    
    NotificationHelper::notifyLeaveRequest($testUser['id'], $testUser['name'], 999);
    echo "<p>✅ Leave notification created</p>";
    
    NotificationHelper::notifyExpenseClaim($testUser['id'], $testUser['name'], 500, 999);
    echo "<p>✅ Expense notification created</p>";
    
    NotificationHelper::notifyAdvanceRequest($testUser['id'], $testUser['name'], 1000, 999);
    echo "<p>✅ Advance notification created</p>";
    
    // Check notifications for owners
    echo "<h3>Checking Owner Notifications</h3>";
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notificationModel = new Notification();
    
    foreach ($owners as $owner) {
        $notifications = $notificationModel->getForUser($owner['id']);
        echo "<p>Owner {$owner['name']} has " . count($notifications) . " notifications</p>";
        
        if (!empty($notifications)) {
            echo "<ul>";
            foreach (array_slice($notifications, 0, 5) as $notif) {
                echo "<li><strong>{$notif['title']}</strong>: {$notif['message']}</li>";
            }
            echo "</ul>";
        }
    }
    
    // Check recent notifications in database
    echo "<h3>Recent Notifications in Database</h3>";
    $stmt = $db->query("SELECT n.*, u.name as sender_name, ur.name as receiver_name, ur.role as receiver_role FROM notifications n LEFT JOIN users u ON n.sender_id = u.id LEFT JOIN users ur ON n.receiver_id = ur.id ORDER BY n.created_at DESC LIMIT 5");
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentNotifications)) {
        echo "<p>❌ No notifications found in database</p>";
    } else {
        echo "<table border='1' style='width:100%'>";
        echo "<tr><th>Sender</th><th>Receiver</th><th>Role</th><th>Title</th><th>Category</th><th>Created</th></tr>";
        foreach ($recentNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['sender_name']}</td>";
            echo "<td>{$notif['receiver_name']}</td>";
            echo "<td>{$notif['receiver_role']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>{$notif['category']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>