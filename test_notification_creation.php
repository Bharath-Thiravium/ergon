<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/NotificationHelper.php';

try {
    $db = Database::connect();
    
    echo "<h2>Testing Notification Creation</h2>";
    
    // Get a test user (sender)
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'user' LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p>❌ No test user found. Creating one...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test User', 'test@example.com', ?, 'user', 'active')");
        $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
        $testUserId = $db->lastInsertId();
        $testUser = ['id' => $testUserId, 'name' => 'Test User', 'role' => 'user'];
    }
    
    // Get owners
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Test User: {$testUser['name']} (ID: {$testUser['id']})</p>";
    echo "<p>Found " . count($owners) . " owners:</p>";
    foreach ($owners as $owner) {
        echo "<p>- {$owner['name']} (ID: {$owner['id']})</p>";
    }
    
    if (empty($owners)) {
        echo "<p>❌ No owners found! This is the problem. Creating a test owner...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Owner', 'owner@example.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "<p>✅ Created test owner with ID: {$ownerId}</p>";
        
        // Refresh owners list
        $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'owner'");
        $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Test creating a leave notification
    echo "<h3>Testing Leave Notification Creation</h3>";
    try {
        NotificationHelper::notifyLeaveRequest($testUser['id'], $testUser['name'], 123);
        echo "<p>✅ Leave notification created successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating leave notification: " . $e->getMessage() . "</p>";
    }
    
    // Test creating an expense notification
    echo "<h3>Testing Expense Notification Creation</h3>";
    try {
        NotificationHelper::notifyExpenseClaim($testUser['id'], $testUser['name'], 500.00, 456);
        echo "<p>✅ Expense notification created successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating expense notification: " . $e->getMessage() . "</p>";
    }
    
    // Test creating an advance notification
    echo "<h3>Testing Advance Notification Creation</h3>";
    try {
        NotificationHelper::notifyAdvanceRequest($testUser['id'], $testUser['name'], 1000.00, 789);
        echo "<p>✅ Advance notification created successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating advance notification: " . $e->getMessage() . "</p>";
    }
    
    // Check if notifications were created
    echo "<h3>Checking Created Notifications</h3>";
    $stmt = $db->query("SELECT n.*, u.name as sender_name, ur.name as receiver_name, ur.role as receiver_role FROM notifications n LEFT JOIN users u ON n.sender_id = u.id LEFT JOIN users ur ON n.receiver_id = ur.id ORDER BY n.created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<p>❌ No notifications found after creation attempts</p>";
    } else {
        echo "<table border='1' style='width:100%'>";
        echo "<tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Receiver Role</th><th>Title</th><th>Message</th><th>Category</th><th>Reference</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['sender_name']}</td>";
            echo "<td>{$notif['receiver_name']}</td>";
            echo "<td>{$notif['receiver_role']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
            echo "<td>{$notif['category']}</td>";
            echo "<td>{$notif['reference_type']}#{$notif['reference_id']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the getForUser method for owners
    echo "<h3>Testing getForUser Method for Owners</h3>";
    require_once __DIR__ . '/app/models/Notification.php';
    $notificationModel = new Notification();
    
    foreach ($owners as $owner) {
        $ownerNotifications = $notificationModel->getForUser($owner['id']);
        echo "<p>Owner {$owner['name']} (ID: {$owner['id']}) has " . count($ownerNotifications) . " notifications</p>";
        
        if (!empty($ownerNotifications)) {
            echo "<ul>";
            foreach ($ownerNotifications as $notif) {
                echo "<li>{$notif['title']} - {$notif['message']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>