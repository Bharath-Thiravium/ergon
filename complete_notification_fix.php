<?php
// Complete Notification System Fix
session_start();

echo "<h1>Complete Notification System Fix</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // 1. Ensure owner user exists
    $stmt = $db->query("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$owner) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('System Owner', 'owner@ergon.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('admin123', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "<div class='success'>✓ Created owner user (ID: $ownerId)</div>";
    } else {
        $ownerId = $owner['id'];
        echo "<div class='info'>Owner user exists (ID: $ownerId)</div>";
    }
    
    // 2. Set session to owner
    $_SESSION['user_id'] = $ownerId;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'System Owner';
    
    // 3. Clear old notifications and create fresh ones
    $db->exec("DELETE FROM notifications");
    echo "<div class='info'>Cleared old notifications</div>";
    
    // 4. Create test employee if needed
    $stmt = $db->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Employee', 'employee@ergon.com', ?, 'user', 'active')");
        $stmt->execute([password_hash('user123', PASSWORD_BCRYPT)]);
        $employeeId = $db->lastInsertId();
        echo "<div class='success'>✓ Created employee user (ID: $employeeId)</div>";
    } else {
        $employeeId = $employee['id'];
    }
    
    // 5. Create live test data with notifications
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    
    // Create leave request
    $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES (?, 'sick', CURDATE(), CURDATE(), 1, 'Live notification test', 'pending')");
    $stmt->execute([$employeeId]);
    $leaveId = $db->lastInsertId();
    NotificationHelper::notifyOwners($employeeId, 'leave', 'request', 'Employee requested sick leave for today', $leaveId);
    
    // Create expense claim
    $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, status) VALUES (?, 'Travel', 500.00, 'Live notification test expense', 'pending')");
    $stmt->execute([$employeeId]);
    $expenseId = $db->lastInsertId();
    NotificationHelper::notifyOwners($employeeId, 'expense', 'claim', 'Employee submitted expense claim of ₹500.00', $expenseId);
    
    // Create advance request
    $stmt = $db->prepare("INSERT INTO advances (user_id, type, amount, reason, requested_date, status) VALUES (?, 'Emergency', 2000.00, 'Live notification test', CURDATE(), 'pending')");
    $stmt->execute([$employeeId]);
    $advanceId = $db->lastInsertId();
    NotificationHelper::notifyOwners($employeeId, 'advance', 'request', 'Employee requested advance of ₹2000.00', $advanceId);
    
    // Create task assignment
    $stmt = $db->prepare("INSERT INTO tasks (title, assigned_by, assigned_to, status) VALUES ('Live Test Task Assignment', ?, ?, 'assigned')");
    $stmt->execute([$ownerId, $employeeId]);
    $taskId = $db->lastInsertId();
    NotificationHelper::notifyOwners($ownerId, 'task', 'assigned', 'New task assigned to Employee: Live Test Task Assignment', $taskId);
    
    // Create followup
    $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, follow_up_date, original_date, status) VALUES (?, 'Live Test Followup', 'Test Company', CURDATE(), CURDATE(), 'pending')");
    $stmt->execute([$employeeId]);
    $followupId = $db->lastInsertId();
    NotificationHelper::notifyOwners($employeeId, 'followup', 'created', 'Employee created followup: Live Test Followup', $followupId);
    
    echo "<div class='success'>✓ Created 5 live test notifications</div>";
    
    // 6. Verify notifications were created
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ?");
    $stmt->execute([$ownerId]);
    $count = $stmt->fetchColumn();
    
    echo "<div class='info'>Total notifications for owner: $count</div>";
    
    if ($count > 0) {
        echo "<div class='success'>🎉 SUCCESS! Notifications are working!</div>";
        echo "<p><strong>Now visit:</strong> <a href='/ergon/notifications' target='_blank'>Owner Notifications</a></p>";
        echo "<p><strong>Login as:</strong> owner@ergon.com / admin123</p>";
    } else {
        echo "<div class='error'>❌ No notifications created</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>