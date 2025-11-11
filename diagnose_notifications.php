<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>🔍 Notification System Diagnosis</h2>";

// 1. Check session
echo "<h3>1. Session Check</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";

// 2. Check notifications table structure
echo "<h3>2. Notifications Table Structure</h3>";
try {
    $result = $pdo->query("DESCRIBE notifications");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// 3. Check existing notifications
echo "<h3>3. Existing Notifications</h3>";
try {
    $result = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = $result->fetchAll();
    echo "Total notifications: " . count($notifications) . "<br>";
    if (count($notifications) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Module</th><th>Action</th><th>Message</th><th>Created</th></tr>";
        foreach($notifications as $n) {
            echo "<tr><td>{$n['id']}</td><td>{$n['sender_id']}</td><td>{$n['receiver_id']}</td><td>{$n['module_name']}</td><td>{$n['action_type']}</td><td>" . substr($n['message'], 0, 50) . "...</td><td>{$n['created_at']}</td></tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// 4. Test NotificationHelper
echo "<h3>4. Test NotificationHelper</h3>";
try {
    require_once 'app/helpers/NotificationHelper.php';
    echo "✅ NotificationHelper loaded successfully<br>";
    
    // Test creating a notification
    NotificationHelper::notifyOwners(2, 'test', 'debug', 'This is a test notification from diagnosis', 999);
    echo "✅ Test notification created<br>";
    
} catch(Exception $e) {
    echo "❌ NotificationHelper Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// 5. Test Notification Model
echo "<h3>5. Test Notification Model</h3>";
try {
    require_once 'app/models/Notification.php';
    $notification = new Notification();
    echo "✅ Notification model loaded<br>";
    
    // Test getForUser
    $notifications = $notification->getForUser(1);
    echo "✅ getForUser works, returned " . count($notifications) . " notifications<br>";
    
} catch(Exception $e) {
    echo "❌ Notification Model Error: " . $e->getMessage() . "<br>";
}

// 6. Check users table
echo "<h3>6. Users Table Check</h3>";
try {
    $result = $pdo->query("SELECT id, name, role FROM users WHERE role = 'owner' LIMIT 5");
    $owners = $result->fetchAll();
    echo "Owner users found: " . count($owners) . "<br>";
    foreach($owners as $owner) {
        echo "- ID: {$owner['id']}, Name: {$owner['name']}, Role: {$owner['role']}<br>";
    }
} catch(Exception $e) {
    echo "❌ Users Error: " . $e->getMessage();
}

// 7. Test direct notification creation
echo "<h3>7. Direct Notification Creation Test</h3>";
try {
    $stmt = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, user_id, title, message, module_name, action_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([2, 1, 1, 'Direct Test', 'This is a direct database test', 'test', 'direct']);
    
    if ($result) {
        echo "✅ Direct notification created successfully<br>";
        echo "Notification ID: " . $pdo->lastInsertId() . "<br>";
    } else {
        echo "❌ Direct notification failed<br>";
    }
} catch(Exception $e) {
    echo "❌ Direct creation error: " . $e->getMessage() . "<br>";
}

// 8. Check NotificationController
echo "<h3>8. NotificationController Test</h3>";
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

try {
    require_once 'app/controllers/NotificationController.php';
    echo "✅ NotificationController loaded<br>";
} catch(Exception $e) {
    echo "❌ NotificationController Error: " . $e->getMessage() . "<br>";
}

// 9. Final verification
echo "<h3>9. Final Verification</h3>";
try {
    $result = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE receiver_id = ?");
    $result->execute([1]);
    $count = $result->fetch()['count'];
    echo "Total notifications for user 1: <strong>$count</strong><br>";
    
    if ($count > 0) {
        echo "✅ Notifications exist in database<br>";
        echo "<a href='/ergon/notifications' target='_blank'>🔗 Check Notifications Page</a><br>";
    } else {
        echo "❌ No notifications found for user 1<br>";
    }
} catch(Exception $e) {
    echo "❌ Verification error: " . $e->getMessage();
}
?>