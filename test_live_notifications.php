<?php
// Test Live Notifications - Create Real Items and Check Notifications
session_start();

// Set session to employee to test
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'user';
$_SESSION['user_name'] = 'Test Employee';

echo "<h1>Test Live Notifications</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    echo "<h2>Creating Test Items as Employee</h2>";
    
    // 1. Create Leave Request
    echo "<h3>1. Creating Leave Request</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES (?, 'sick', CURDATE(), CURDATE(), 1, 'Live test leave request', 'pending')");
        $stmt->execute([2]);
        $leaveId = $db->lastInsertId();
        
        // Trigger notification manually (simulating controller)
        require_once __DIR__ . '/app/helpers/NotificationHelper.php';
        NotificationHelper::notifyOwners(2, 'leave', 'request', 'Test Employee requested sick leave for today', $leaveId);
        echo "<div class='success'>✓ Leave request created with ID: $leaveId</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Leave creation failed: " . $e->getMessage() . "</div>";
    }
    
    // 2. Create Expense Claim
    echo "<h3>2. Creating Expense Claim</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, status) VALUES (?, 'Travel', 150.00, 'Live test expense claim', 'pending')");
        $stmt->execute([2]);
        $expenseId = $db->lastInsertId();
        
        NotificationHelper::notifyOwners(2, 'expense', 'claim', 'Test Employee submitted expense claim of ₹150.00 for Travel', $expenseId);
        echo "<div class='success'>✓ Expense claim created with ID: $expenseId</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Expense creation failed: " . $e->getMessage() . "</div>";
    }
    
    // 3. Create Advance Request
    echo "<h3>3. Creating Advance Request</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO advances (user_id, type, amount, reason, requested_date, status) VALUES (?, 'Emergency', 2000.00, 'Live test advance request', CURDATE(), 'pending')");
        $stmt->execute([2]);
        $advanceId = $db->lastInsertId();
        
        NotificationHelper::notifyOwners(2, 'advance', 'request', 'Test Employee requested emergency advance of ₹2000.00', $advanceId);
        echo "<div class='success'>✓ Advance request created with ID: $advanceId</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Advance creation failed: " . $e->getMessage() . "</div>";
    }
    
    // 4. Create Follow-up
    echo "<h3>4. Creating Follow-up</h3>";
    try {
        $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, follow_up_date, original_date, status) VALUES (?, 'Live Test Follow-up', 'ABC Company', CURDATE(), CURDATE(), 'pending')");
        $stmt->execute([2]);
        $followupId = $db->lastInsertId();
        
        NotificationHelper::notifyOwners(2, 'followup', 'created', 'Test Employee created follow-up: Live Test Follow-up', $followupId);
        echo "<div class='success'>✓ Follow-up created with ID: $followupId</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Follow-up creation failed: " . $e->getMessage() . "</div>";
    }
    
    // Check notifications created
    echo "<h2>Checking Notifications</h2>";
    $stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $recentCount = $stmt->fetchColumn();
    echo "<div class='info'>Recent notifications (last 5 minutes): $recentCount</div>";
    
    if ($recentCount > 0) {
        $stmt = $db->query("SELECT n.*, u.name as sender_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY n.created_at DESC");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Notifications:</h3>";
        foreach ($recent as $notif) {
            echo "<div><strong>{$notif['module_name']}</strong> - {$notif['message']} (from {$notif['sender_name']}) - Receiver: {$notif['receiver_id']}</div>";
        }
    }
    
    // Check owner users
    echo "<h2>Owner Users</h2>";
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($owners)) {
        echo "<div class='error'>✗ No owner users found! Creating one...</div>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Owner', 'owner@test.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "<div class='success'>✓ Created owner user with ID: $ownerId</div>";
    } else {
        foreach ($owners as $owner) {
            echo "<div class='info'>Owner: {$owner['name']} (ID: {$owner['id']})</div>";
        }
    }
    
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>Switch to owner session: <a href='/ergon/debug_notifications.php'>Debug Script</a></li>";
    echo "<li>View notifications as owner: <a href='/ergon/notifications'>Owner Notifications</a></li>";
    echo "<li>Create items through UI to test real triggers</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>