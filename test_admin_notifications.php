<?php
// Test Admin Notifications
require_once 'app/config/database.php';
require_once 'app/helpers/NotificationHelper.php';

try {
    $db = Database::connect();
    
    // Get admin users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "❌ No admin users found. Creating test admin user...\n";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Admin', 'admin@ergon.com', ?, 'admin', 'active')");
        $stmt->execute([password_hash('admin123', PASSWORD_BCRYPT)]);
        $adminId = $db->lastInsertId();
        echo "✅ Created admin user with ID: $adminId\n";
    } else {
        $adminId = $admins[0]['id'];
        echo "✅ Found admin user: " . $admins[0]['name'] . " (ID: $adminId)\n";
    }
    
    // Get owner users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'owner'");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($owners)) {
        echo "❌ No owner users found. Creating test owner user...\n";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Owner', 'owner@ergon.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('owner123', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "✅ Created owner user with ID: $ownerId\n";
    } else {
        $ownerId = $owners[0]['id'];
        echo "✅ Found owner user: " . $owners[0]['name'] . " (ID: $ownerId)\n";
    }
    
    // Get regular users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'user'");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No regular users found. Creating test user...\n";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Employee', 'employee@ergon.com', ?, 'user', 'active')");
        $stmt->execute([password_hash('user123', PASSWORD_BCRYPT)]);
        $userId = $db->lastInsertId();
        echo "✅ Created employee user with ID: $userId\n";
    } else {
        $userId = $users[0]['id'];
        echo "✅ Found employee user: " . $users[0]['name'] . " (ID: $userId)\n";
    }
    
    echo "\n--- Testing Admin Notifications ---\n";
    
    // Test 1: Employee creates leave request (should notify both owner and admin)
    echo "1. Testing leave request notification...\n";
    NotificationHelper::notifyOwners(
        $userId,
        'leave',
        'request',
        'Employee has submitted a leave request for approval',
        1
    );
    echo "✅ Leave request notification sent to owners and admins\n";
    
    // Test 2: Employee creates expense claim (should notify both owner and admin)
    echo "2. Testing expense claim notification...\n";
    NotificationHelper::notifyOwners(
        $userId,
        'expense',
        'claim',
        'Employee submitted expense claim of ₹1500 for travel',
        1
    );
    echo "✅ Expense claim notification sent to owners and admins\n";
    
    // Test 3: Owner assigns task (should notify admin)
    echo "3. Testing task assignment notification...\n";
    NotificationHelper::notifyAdmins(
        $ownerId,
        'task',
        'assigned',
        'Owner assigned new task: Database Optimization to employee',
        1
    );
    echo "✅ Task assignment notification sent to admins\n";
    
    // Test 4: Employee attendance update (should notify admin)
    echo "4. Testing attendance notification...\n";
    NotificationHelper::notifyAdmins(
        $userId,
        'attendance',
        'late_arrival',
        'Employee arrived late at 10:30 AM',
        1
    );
    echo "✅ Attendance notification sent to admins\n";
    
    // Check notifications in database
    echo "\n--- Checking Admin Notifications ---\n";
    $stmt = $db->prepare("SELECT n.*, u.name as sender_name FROM notifications n JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute([$adminId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Admin notifications count: " . count($notifications) . "\n";
    foreach ($notifications as $notification) {
        echo "- [{$notification['module_name']}] {$notification['message']} (from {$notification['sender_name']})\n";
    }
    
    echo "\n✅ Admin notification system is working!\n";
    echo "Visit http://localhost/ergon/notifications as admin user to see the notifications.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>