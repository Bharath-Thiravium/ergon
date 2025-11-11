<?php
// Complete Notification System Test
session_start();

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'Test Owner';
}

echo "<h1>Complete Notification System Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test{background:#f0f8ff;padding:10px;margin:10px 0;border-left:4px solid #007cba;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    $db = Database::connect();
    echo "<div class='success'>✓ Database connected</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

$testResults = [];

// Test 1: Create test users if needed
echo "<div class='test'><h2>Test 1: Ensure Test Users Exist</h2>";
try {
    // Check if we have an owner
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'owner'");
    $ownerCount = $stmt->fetchColumn();
    
    if ($ownerCount == 0) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'owner', 'active')");
        $stmt->execute(['Test Owner', 'owner@test.com', password_hash('password', PASSWORD_BCRYPT)]);
        echo "<div class='info'>Created test owner user</div>";
    }
    
    // Check if we have regular users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
        $stmt->execute(['Test Employee', 'employee@test.com', password_hash('password', PASSWORD_BCRYPT)]);
        echo "<div class='info'>Created test employee user</div>";
    }
    
    echo "<div class='success'>✓ Test users available</div>";
    $testResults['users'] = true;
} catch (Exception $e) {
    echo "<div class='error'>✗ User setup failed: " . $e->getMessage() . "</div>";
    $testResults['users'] = false;
}
echo "</div>";

// Test 2: Test Leave Notification
echo "<div class='test'><h2>Test 2: Leave Request Notification</h2>";
try {
    // Get a test user
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        // Create a test leave request
        $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES (?, 'sick', CURDATE(), CURDATE(), 1, 'Test leave notification', 'pending')");
        $stmt->execute([$testUser['id']]);
        $leaveId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            $testUser['id'],
            'leave',
            'request',
            "{$testUser['name']} has requested leave for testing notification system",
            $leaveId
        );
        
        echo "<div class='success'>✓ Leave notification created</div>";
        $testResults['leave'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['leave'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Leave notification failed: " . $e->getMessage() . "</div>";
    $testResults['leave'] = false;
}
echo "</div>";

// Test 3: Test Expense Notification
echo "<div class='test'><h2>Test 3: Expense Claim Notification</h2>";
try {
    if ($testUser) {
        // Create a test expense
        $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, status) VALUES (?, 'Travel', 250.00, 'Test expense notification', 'pending')");
        $stmt->execute([$testUser['id']]);
        $expenseId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            $testUser['id'],
            'expense',
            'claim',
            "{$testUser['name']} submitted expense claim of ₹250.00 for testing",
            $expenseId
        );
        
        echo "<div class='success'>✓ Expense notification created</div>";
        $testResults['expense'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['expense'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Expense notification failed: " . $e->getMessage() . "</div>";
    $testResults['expense'] = false;
}
echo "</div>";

// Test 4: Test Task Notification
echo "<div class='test'><h2>Test 4: Task Assignment Notification</h2>";
try {
    if ($testUser) {
        // Create a test task
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, status) VALUES (?, 'Test task for notification system', 1, ?, 'assigned')");
        $stmt->execute(['Test Notification Task', $testUser['id']]);
        $taskId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            1,
            'task',
            'assigned',
            "New task 'Test Notification Task' assigned to {$testUser['name']}",
            $taskId
        );
        
        echo "<div class='success'>✓ Task notification created</div>";
        $testResults['task'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['task'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Task notification failed: " . $e->getMessage() . "</div>";
    $testResults['task'] = false;
}
echo "</div>";

// Test 5: Test Advance Notification
echo "<div class='test'><h2>Test 5: Advance Request Notification</h2>";
try {
    if ($testUser) {
        // Create a test advance
        $stmt = $db->prepare("INSERT INTO advances (user_id, type, amount, reason, requested_date, status) VALUES (?, 'Salary Advance', 5000.00, 'Test advance notification', CURDATE(), 'pending')");
        $stmt->execute([$testUser['id']]);
        $advanceId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            $testUser['id'],
            'advance',
            'request',
            "{$testUser['name']} requested advance of ₹5000.00",
            $advanceId
        );
        
        echo "<div class='success'>✓ Advance notification created</div>";
        $testResults['advance'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['advance'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Advance notification failed: " . $e->getMessage() . "</div>";
    $testResults['advance'] = false;
}
echo "</div>";

// Test 6: Test Followup Notification
echo "<div class='test'><h2>Test 6: Follow-up Creation Notification</h2>";
try {
    if ($testUser) {
        // Create a test followup
        $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, follow_up_date, original_date, status) VALUES (?, 'Test Follow-up Notification', 'Test Company', CURDATE(), CURDATE(), 'pending')");
        $stmt->execute([$testUser['id']]);
        $followupId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            $testUser['id'],
            'followup',
            'created',
            "{$testUser['name']} created follow-up: Test Follow-up Notification",
            $followupId
        );
        
        echo "<div class='success'>✓ Follow-up notification created</div>";
        $testResults['followup'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['followup'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Follow-up notification failed: " . $e->getMessage() . "</div>";
    $testResults['followup'] = false;
}
echo "</div>";

// Test 7: Test Attendance Notification
echo "<div class='test'><h2>Test 7: Late Arrival Notification</h2>";
try {
    if ($testUser) {
        // Create a late attendance record
        $lateTime = date('Y-m-d') . ' 10:30:00'; // 10:30 AM (late)
        $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status) VALUES (?, ?, 'present')");
        $stmt->execute([$testUser['id'], $lateTime]);
        $attendanceId = $db->lastInsertId();
        
        // Trigger notification
        NotificationHelper::notifyOwners(
            $testUser['id'],
            'attendance',
            'late_arrival',
            "{$testUser['name']} arrived late at 10:30 AM",
            $attendanceId
        );
        
        echo "<div class='success'>✓ Attendance notification created</div>";
        $testResults['attendance'] = true;
    } else {
        echo "<div class='error'>✗ No test user available</div>";
        $testResults['attendance'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Attendance notification failed: " . $e->getMessage() . "</div>";
    $testResults['attendance'] = false;
}
echo "</div>";

// Test 8: Verify Notifications in Database
echo "<div class='test'><h2>Test 8: Verify Notifications in Database</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE created_at >= CURDATE()");
    $todayCount = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT n.*, u.name as sender_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id WHERE n.created_at >= CURDATE() ORDER BY n.created_at DESC");
    $todayNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Today's notifications: $todayCount</div>";
    
    if ($todayCount > 0) {
        echo "<h3>Latest Notifications:</h3>";
        echo "<ul>";
        foreach ($todayNotifications as $notif) {
            echo "<li><strong>{$notif['module_name']}</strong> - {$notif['message']} (from {$notif['sender_name']}) - {$notif['created_at']}</li>";
        }
        echo "</ul>";
        echo "<div class='success'>✓ Notifications are being stored correctly</div>";
        $testResults['database'] = true;
    } else {
        echo "<div class='error'>✗ No notifications found in database</div>";
        $testResults['database'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Database verification failed: " . $e->getMessage() . "</div>";
    $testResults['database'] = false;
}
echo "</div>";

// Test 9: Test Notification Model
echo "<div class='test'><h2>Test 9: Test Notification Model</h2>";
try {
    require_once __DIR__ . '/app/models/Notification.php';
    $notificationModel = new Notification();
    
    // Get notifications for owner
    $notifications = $notificationModel->getForUser(1, 10);
    echo "<div class='info'>Retrieved " . count($notifications) . " notifications for owner</div>";
    
    // Get unread count
    $unreadCount = $notificationModel->getUnreadCount(1);
    echo "<div class='info'>Unread notifications: $unreadCount</div>";
    
    if (count($notifications) > 0) {
        echo "<div class='success'>✓ Notification model working correctly</div>";
        $testResults['model'] = true;
    } else {
        echo "<div class='error'>✗ Notification model not returning data</div>";
        $testResults['model'] = false;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Notification model test failed: " . $e->getMessage() . "</div>";
    $testResults['model'] = false;
}
echo "</div>";

// Test Summary
echo "<h2>Test Summary</h2>";
$passedTests = array_sum($testResults);
$totalTests = count($testResults);

echo "<div class='info'>Passed: $passedTests / $totalTests tests</div>";

if ($passedTests == $totalTests) {
    echo "<div class='success'><h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>The notification system is working correctly. You can now:</p>";
    echo "<ul>";
    echo "<li>Visit <a href='/ergon/notifications' target='_blank'>/ergon/notifications</a> to see notifications</li>";
    echo "<li>Create new leaves, expenses, tasks, etc. to generate real notifications</li>";
    echo "<li>Test the mark as read functionality</li>";
    echo "</ul></div>";
} else {
    echo "<div class='error'><h3>❌ Some tests failed</h3>";
    echo "<p>Failed tests:</p><ul>";
    foreach ($testResults as $test => $result) {
        if (!$result) {
            echo "<li>$test</li>";
        }
    }
    echo "</ul></div>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Visit <a href='/ergon/notifications' target='_blank'>/ergon/notifications</a></li>";
echo "<li>Run <a href='/ergon/backfill_notifications.php' target='_blank'>backfill script</a> to create notifications for existing data</li>";
echo "<li>Test creating new items (leaves, expenses, etc.) to see real-time notifications</li>";
echo "</ol>";
?>