<?php
// Final Notification Fix - Root Cause Analysis & Complete Solution
session_start();

echo "<h1>Final Notification Fix</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .step{background:#f0f8ff;padding:10px;margin:10px 0;border-left:4px solid #007cba;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    echo "<div class='step'><h2>Step 1: Database Analysis</h2>";
    
    // Check notifications table
    $stmt = $db->query("SELECT COUNT(*) FROM notifications");
    $totalNotifications = $stmt->fetchColumn();
    echo "<div class='info'>Total notifications in database: $totalNotifications</div>";
    
    // Check users
    $stmt = $db->query("SELECT id, name, role FROM users ORDER BY role, id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'>Users in system:</div>";
    foreach ($users as $user) {
        echo "<div>- ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}</div>";
    }
    
    // Find owner users
    $owners = array_filter($users, fn($u) => $u['role'] === 'owner');
    if (empty($owners)) {
        echo "<div class='error'>❌ ROOT CAUSE: No owner users found!</div>";
        // Create owner user
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('System Owner', 'owner@ergon.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('admin123', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "<div class='success'>✓ Created owner user with ID: $ownerId</div>";
        $owners = [['id' => $ownerId, 'name' => 'System Owner', 'role' => 'owner']];
    } else {
        echo "<div class='success'>✓ Found " . count($owners) . " owner user(s)</div>";
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Step 2: Session Fix</h2>";
    $currentUserId = $_SESSION['user_id'] ?? null;
    $currentRole = $_SESSION['role'] ?? null;
    echo "<div class='info'>Current session - User ID: $currentUserId, Role: $currentRole</div>";
    
    // Set session to owner
    $_SESSION['user_id'] = $owners[0]['id'];
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = $owners[0]['name'];
    echo "<div class='success'>✓ Session set to owner ID: {$owners[0]['id']}</div>";
    echo "</div>";
    
    echo "<div class='step'><h2>Step 3: Create Test Notifications</h2>";
    
    // Create notifications for owner
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    
    // Get or create test employee
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1");
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Test Employee', 'employee@ergon.com', ?, 'user', 'active')");
        $stmt->execute([password_hash('user123', PASSWORD_BCRYPT)]);
        $employeeId = $db->lastInsertId();
        $employee = ['id' => $employeeId, 'name' => 'Test Employee'];
        echo "<div class='info'>Created test employee with ID: $employeeId</div>";
    }
    
    // Create test items and notifications
    $testItems = [
        'leave' => "INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES ({$employee['id']}, 'sick', CURDATE(), CURDATE(), 1, 'Final test leave', 'pending')",
        'expense' => "INSERT INTO expenses (user_id, category, amount, description, status) VALUES ({$employee['id']}, 'Travel', 200.00, 'Final test expense', 'pending')",
        'advance' => "INSERT INTO advances (user_id, type, amount, reason, requested_date, status) VALUES ({$employee['id']}, 'Emergency', 3000.00, 'Final test advance', CURDATE(), 'pending')",
        'task' => "INSERT INTO tasks (title, assigned_by, assigned_to, status) VALUES ('Final Test Task', 1, {$employee['id']}, 'assigned')",
        'followup' => "INSERT INTO followups (user_id, title, company_name, follow_up_date, original_date, status) VALUES ({$employee['id']}, 'Final Test Followup', 'Test Corp', CURDATE(), CURDATE(), 'pending')"
    ];
    
    foreach ($testItems as $type => $sql) {
        try {
            $db->exec($sql);
            $itemId = $db->lastInsertId();
            
            // Create notification
            $messages = [
                'leave' => "{$employee['name']} requested leave (Final Test)",
                'expense' => "{$employee['name']} submitted expense claim of ₹200.00 (Final Test)",
                'advance' => "{$employee['name']} requested advance of ₹3000.00 (Final Test)",
                'task' => "New task 'Final Test Task' assigned to {$employee['name']}",
                'followup' => "{$employee['name']} created follow-up: Final Test Followup"
            ];
            
            NotificationHelper::notifyOwners($employee['id'], $type, 'request', $messages[$type], $itemId);
            echo "<div class='success'>✓ Created $type notification</div>";
        } catch (Exception $e) {
            echo "<div class='error'>✗ Failed to create $type: " . $e->getMessage() . "</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Step 4: Verify Notifications</h2>";
    
    // Check notifications for current owner
    $stmt = $db->prepare("SELECT n.*, u.name as sender_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute([$_SESSION['user_id']]);
    $ownerNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Notifications for owner (ID: {$_SESSION['user_id']}): " . count($ownerNotifications) . "</div>";
    
    if (!empty($ownerNotifications)) {
        echo "<h3>Latest Notifications:</h3>";
        foreach ($ownerNotifications as $notif) {
            echo "<div><strong>{$notif['module_name']}</strong> - {$notif['message']} (from {$notif['sender_name']}) - {$notif['created_at']}</div>";
        }
        echo "<div class='success'>✓ Notifications are working correctly!</div>";
    } else {
        echo "<div class='error'>❌ No notifications found for owner</div>";
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Step 5: Test Notification Controller</h2>";
    
    // Test notification model
    require_once __DIR__ . '/app/models/Notification.php';
    $notificationModel = new Notification();
    $notifications = $notificationModel->getForUser($_SESSION['user_id']);
    echo "<div class='info'>Notification model returned: " . count($notifications) . " notifications</div>";
    
    if (!empty($notifications)) {
        echo "<div class='success'>✓ Notification model is working</div>";
    } else {
        echo "<div class='error'>❌ Notification model not returning data</div>";
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Final Results</h2>";
    
    if (!empty($ownerNotifications) && !empty($notifications)) {
        echo "<div class='success'><h3>🎉 SUCCESS! Notifications are working!</h3>";
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li><a href='/ergon/notifications' target='_blank'>View Owner Notifications</a></li>";
        echo "<li>Create new items to test real-time notifications</li>";
        echo "<li>Login as owner with: owner@ergon.com / admin123</li>";
        echo "</ul></div>";
    } else {
        echo "<div class='error'><h3>❌ Still not working</h3>";
        echo "<p>Check:</p>";
        echo "<ul>";
        echo "<li>Database permissions</li>";
        echo "<li>Session handling</li>";
        echo "<li>Controller includes</li>";
        echo "</ul></div>";
    }
    
    echo "<h3>Login Credentials:</h3>";
    echo "<div class='info'>Owner: owner@ergon.com / admin123</div>";
    echo "<div class='info'>Employee: employee@ergon.com / user123</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Fatal Error: " . $e->getMessage() . "</div>";
}
?>