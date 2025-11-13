<?php
// Test Live Notifications - Verify real-time notification system
require_once 'app/config/database.php';
require_once 'app/helpers/NotificationHelper.php';

echo "<h2>TESTING LIVE NOTIFICATIONS SYSTEM</h2>";

try {
    $db = Database::connect();
    
    // 1. Get users
    $stmt = $db->prepare("SELECT id, name, role FROM users ORDER BY role, name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Available Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['id']} - {$user['name']} ({$user['role']})</li>";
    }
    echo "</ul>";
    
    // 2. Find owner and employee
    $owner = null;
    $employee = null;
    foreach ($users as $user) {
        if ($user['role'] === 'owner' && !$owner) $owner = $user;
        if ($user['role'] === 'user' && !$employee) $employee = $user;
    }
    
    if (!$owner || !$employee) {
        echo "<p style='color: red;'>❌ Need both owner and employee users for testing</p>";
        exit;
    }
    
    echo "<h3>Test Scenario: Employee creates leave request</h3>";
    echo "<p>Employee: {$employee['name']} (ID: {$employee['id']})</p>";
    echo "<p>Owner: {$owner['name']} (ID: {$owner['id']})</p>";
    
    // 3. Clear existing notifications
    $stmt = $db->prepare("DELETE FROM notifications WHERE receiver_id = ?");
    $stmt->execute([$owner['id']]);
    echo "<p>✅ Cleared existing notifications</p>";
    
    // 4. Test notification creation
    echo "<h3>Creating Test Notifications:</h3>";
    
    // Test 1: Leave request
    NotificationHelper::notifyLeaveRequest($employee['id'], $employee['name']);
    echo "<p>✅ Leave request notification sent</p>";
    
    // Test 2: Expense claim
    NotificationHelper::notifyExpenseClaim($employee['id'], $employee['name'], 1500);
    echo "<p>✅ Expense claim notification sent</p>";
    
    // Test 3: Direct owner notification
    NotificationHelper::notifyOwners(
        $employee['id'],
        'attendance',
        'late_arrival',
        "{$employee['name']} arrived late at 10:30 AM",
        null
    );
    echo "<p>✅ Attendance notification sent</p>";
    
    // 5. Verify notifications were created
    echo "<h3>Verifying Notifications:</h3>";
    $stmt = $db->prepare("SELECT n.*, u.name as sender_name FROM notifications n JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC");
    $stmt->execute([$owner['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Owner notifications count: " . count($notifications) . "</strong></p>";
    
    if (count($notifications) > 0) {
        echo "<ul>";
        foreach ($notifications as $notification) {
            echo "<li>[{$notification['module_name']}] {$notification['message']} (from {$notification['sender_name']})</li>";
        }
        echo "</ul>";
        
        echo "<h3>✅ LIVE NOTIFICATIONS WORKING!</h3>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Login as owner: <a href='/ergon/login'>http://localhost/ergon/login</a></li>";
        echo "<li>Use credentials: owner@ergon.com / owner123</li>";
        echo "<li>Visit notifications: <a href='/ergon/notifications'>http://localhost/ergon/notifications</a></li>";
        echo "<li>You should see " . count($notifications) . " notifications</li>";
        echo "</ol>";
        
        echo "<h3>Test Real Actions:</h3>";
        echo "<p>Now test with real user actions:</p>";
        echo "<ol>";
        echo "<li>Login as employee: {$employee['name']} (create password if needed)</li>";
        echo "<li>Create a leave request at: <a href='/ergon/leaves/create'>http://localhost/ergon/leaves/create</a></li>";
        echo "<li>Create an expense claim at: <a href='/ergon/expenses/create'>http://localhost/ergon/expenses/create</a></li>";
        echo "<li>Check owner notifications should update automatically</li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: red;'>❌ No notifications created - there's still an issue</p>";
        
        // Debug: Check notification table structure
        echo "<h3>Debug: Notification Table Structure</h3>";
        $stmt = $db->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>