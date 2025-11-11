<?php
// Complete Notification System Diagnosis
session_start();

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'Test Owner';
}

echo "<h1>Complete Notification System Diagnosis</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "<div class='success'>✓ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// 1. Check notifications table structure
echo "<h2>1. Notifications Table Structure</h2>";
try {
    $stmt = $db->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='success'>✓ Notifications table exists</div>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - " . $col['Null'] . " - " . $col['Default'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Notifications table issue: " . $e->getMessage() . "</div>";
    
    // Try to create the table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            module_name VARCHAR(50) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_receiver_read (receiver_id, is_read),
            INDEX idx_created_at (created_at)
        )";
        $db->exec($sql);
        echo "<div class='success'>✓ Created notifications table</div>";
    } catch (Exception $e2) {
        echo "<div class='error'>✗ Failed to create notifications table: " . $e2->getMessage() . "</div>";
    }
}

// 2. Check existing notifications
echo "<h2>2. Existing Notifications</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Total notifications: $count</div>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($notifications as $notif) {
            echo "ID: {$notif['id']}, Sender: {$notif['sender_id']}, Receiver: {$notif['receiver_id']}, Module: {$notif['module_name']}, Message: {$notif['message']}\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking notifications: " . $e->getMessage() . "</div>";
}

// 3. Check users table for owners
echo "<h2>3. Owner Users</h2>";
try {
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'>Found " . count($owners) . " owner(s)</div>";
    foreach ($owners as $owner) {
        echo "<div>Owner ID: {$owner['id']}, Name: {$owner['name']}</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking owners: " . $e->getMessage() . "</div>";
}

// 4. Test NotificationHelper
echo "<h2>4. Test NotificationHelper</h2>";
try {
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    echo "<div class='success'>✓ NotificationHelper loaded</div>";
    
    // Test notification creation
    NotificationHelper::notifyOwners(2, 'test', 'diagnosis', 'Test notification from diagnosis script', 999);
    echo "<div class='success'>✓ Test notification created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ NotificationHelper error: " . $e->getMessage() . "</div>";
}

// 5. Test Notification Model
echo "<h2>5. Test Notification Model</h2>";
try {
    require_once __DIR__ . '/app/models/Notification.php';
    $notificationModel = new Notification();
    echo "<div class='success'>✓ Notification model loaded</div>";
    
    // Test getting notifications for owner
    $notifications = $notificationModel->getForUser(1, 10);
    echo "<div class='info'>Retrieved " . count($notifications) . " notifications for user ID 1</div>";
    
    if (!empty($notifications)) {
        echo "<pre>";
        foreach ($notifications as $notif) {
            echo "Message: {$notif['message']}, Created: {$notif['created_at']}\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Notification model error: " . $e->getMessage() . "</div>";
}

// 6. Test pending data in other tables
echo "<h2>6. Pending Data in Other Tables</h2>";

// Check leaves
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Pending leaves: $count</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking leaves: " . $e->getMessage() . "</div>";
}

// Check expenses
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Pending expenses: $count</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking expenses: " . $e->getMessage() . "</div>";
}

// Check tasks
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM tasks WHERE status IN ('assigned', 'in_progress')");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Active tasks: $count</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking tasks: " . $e->getMessage() . "</div>";
}

// 7. Test direct notification creation
echo "<h2>7. Test Direct Notification Creation</h2>";
try {
    $stmt = $db->prepare("INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([2, 1, 'test', 'direct_test', 'Direct test notification from diagnosis', 123]);
    
    if ($result) {
        echo "<div class='success'>✓ Direct notification created successfully</div>";
        $notifId = $db->lastInsertId();
        echo "<div class='info'>Created notification ID: $notifId</div>";
    } else {
        echo "<div class='error'>✗ Direct notification creation failed</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Direct notification error: " . $e->getMessage() . "</div>";
}

// 8. Test notification controller
echo "<h2>8. Test Notification Controller Access</h2>";
try {
    require_once __DIR__ . '/app/controllers/NotificationController.php';
    echo "<div class='success'>✓ NotificationController loaded</div>";
    
    // Check if we can instantiate it
    $controller = new NotificationController();
    echo "<div class='success'>✓ NotificationController instantiated</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ NotificationController error: " . $e->getMessage() . "</div>";
}

// 9. Check final notification count
echo "<h2>9. Final Notification Count</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE receiver_id = 1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Total notifications for owner (ID 1): $count</div>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM notifications WHERE receiver_id = 1 ORDER BY created_at DESC LIMIT 5");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Latest notifications for owner:</h3>";
        echo "<pre>";
        foreach ($notifications as $notif) {
            echo "ID: {$notif['id']}, Module: {$notif['module_name']}, Action: {$notif['action_type']}, Message: {$notif['message']}, Created: {$notif['created_at']}\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking final count: " . $e->getMessage() . "</div>";
}

echo "<h2>Diagnosis Complete</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Visit <a href='/ergon/notifications' target='_blank'>/ergon/notifications</a> to check if notifications appear</li>";
echo "<li>Create a test leave/expense to trigger new notifications</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "</ul>";
?>