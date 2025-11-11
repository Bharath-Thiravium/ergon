<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>🔔 Complete Owner Notification System Test</h2>";

// Set owner session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>✅ System Status</h3>";
echo "Session User ID: <strong>{$_SESSION['user_id']}</strong><br>";
echo "Session Role: <strong>{$_SESSION['role']}</strong><br>";
echo "Database: <strong>Connected</strong><br>";
echo "</div>";

// Clear old notifications for clean test
$pdo->exec("DELETE FROM notifications WHERE receiver_id = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

echo "<h3>🚀 Testing All Notification Triggers</h3>";

require_once 'app/helpers/NotificationHelper.php';

$testResults = [];

// Test 1: Leave Request
try {
    NotificationHelper::notifyOwners(2, 'leave', 'request', 'John Employee requested sick leave from Jan 20-22, 2024', 1);
    $testResults[] = ['✅', 'Leave Request', 'John Employee sick leave notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Leave Request', $e->getMessage()];
}

// Test 2: Expense Claim
try {
    NotificationHelper::notifyOwners(3, 'expense', 'claim', 'Jane Employee submitted travel expense of ₹1,250', 2);
    $testResults[] = ['✅', 'Expense Claim', 'Jane Employee travel expense notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Expense Claim', $e->getMessage()];
}

// Test 3: Advance Request
try {
    NotificationHelper::notifyOwners(4, 'advance', 'request', 'Mike Employee requested salary advance of ₹15,000', 3);
    $testResults[] = ['✅', 'Advance Request', 'Mike Employee salary advance notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Advance Request', $e->getMessage()];
}

// Test 4: Task Assignment
try {
    NotificationHelper::notifyOwners(2, 'task', 'created', 'New urgent task "Client Meeting Prep" assigned to Sarah', 4);
    $testResults[] = ['✅', 'Task Assignment', 'Urgent task assignment notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Task Assignment', $e->getMessage()];
}

// Test 5: Late Attendance
try {
    NotificationHelper::notifyOwners(3, 'attendance', 'late_arrival', 'Jane Employee arrived late at 10:45 AM', 5);
    $testResults[] = ['✅', 'Late Attendance', 'Late arrival alert notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Late Attendance', $e->getMessage()];
}

// Test 6: Daily Planner
try {
    NotificationHelper::notifyOwners(4, 'planner', 'submitted', 'Mike Employee submitted daily plan with 8 tasks', 6);
    $testResults[] = ['✅', 'Daily Planner', 'Daily plan submission notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Daily Planner', $e->getMessage()];
}

// Test 7: Evening Update
try {
    NotificationHelper::notifyOwners(2, 'evening_update', 'submitted', 'John Employee completed evening update with 92% productivity', 7);
    $testResults[] = ['✅', 'Evening Update', 'Evening productivity update notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Evening Update', $e->getMessage()];
}

// Test 8: Follow-up
try {
    NotificationHelper::notifyOwners(3, 'followup', 'created', 'Jane Employee created follow-up for XYZ Corp client meeting', 8);
    $testResults[] = ['✅', 'Follow-up', 'Client follow-up notification'];
} catch(Exception $e) {
    $testResults[] = ['❌', 'Follow-up', $e->getMessage()];
}

// Display test results
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";
foreach($testResults as $result) {
    $bgColor = $result[0] === '✅' ? '#dcfce7' : '#fee2e2';
    $borderColor = $result[0] === '✅' ? '#16a34a' : '#dc2626';
    echo "<div style='background: $bgColor; border: 2px solid $borderColor; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='margin: 0 0 8px 0;'>{$result[0]} {$result[1]}</h4>";
    echo "<p style='margin: 0; color: #374151;'>{$result[2]}</p>";
    echo "</div>";
}
echo "</div>";

// Get and display notifications
echo "<h3>📋 Owner Notifications Dashboard</h3>";
try {
    require_once 'app/models/Notification.php';
    $notification = new Notification();
    $notifications = $notification->getForUser(1);
    
    echo "<div style='background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 15px 0;'>";
    echo "<h4 style='margin: 0 0 15px 0; color: #1f2937;'>📊 Notification Summary</h4>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;'>";
    
    $totalNotifications = count($notifications);
    $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
    $moduleStats = [];
    
    foreach($notifications as $notif) {
        $moduleStats[$notif['module_name']] = ($moduleStats[$notif['module_name']] ?? 0) + 1;
    }
    
    echo "<div style='text-align: center; padding: 15px; background: #f3f4f6; border-radius: 6px;'>";
    echo "<div style='font-size: 24px; font-weight: bold; color: #1f2937;'>$totalNotifications</div>";
    echo "<div style='color: #6b7280; font-size: 14px;'>Total Notifications</div>";
    echo "</div>";
    
    echo "<div style='text-align: center; padding: 15px; background: #fef3c7; border-radius: 6px;'>";
    echo "<div style='font-size: 24px; font-weight: bold; color: #d97706;'>$unreadCount</div>";
    echo "<div style='color: #92400e; font-size: 14px;'>Unread</div>";
    echo "</div>";
    
    echo "<div style='text-align: center; padding: 15px; background: #dcfce7; border-radius: 6px;'>";
    echo "<div style='font-size: 24px; font-weight: bold; color: #16a34a;'>" . count($moduleStats) . "</div>";
    echo "<div style='color: #15803d; font-size: 14px;'>Active Modules</div>";
    echo "</div>";
    
    echo "</div>";
    
    if ($totalNotifications > 0) {
        echo "<h5 style='margin: 20px 0 10px 0; color: #374151;'>📝 Recent Notifications</h5>";
        echo "<div style='max-height: 400px; overflow-y: auto;'>";
        
        $moduleColors = [
            'leave' => '#3b82f6', 'expense' => '#10b981', 'advance' => '#8b5cf6',
            'task' => '#f59e0b', 'attendance' => '#ef4444', 'planner' => '#06b6d4',
            'evening_update' => '#84cc16', 'followup' => '#f97316'
        ];
        
        foreach(array_slice($notifications, 0, 10) as $notif) {
            $color = $moduleColors[$notif['module_name']] ?? '#6b7280';
            $readStatus = $notif['is_read'] ? 'Read' : 'Unread';
            $readBg = $notif['is_read'] ? '#f3f4f6' : '#fffbeb';
            
            echo "<div style='margin: 8px 0; padding: 12px; background: $readBg; border-left: 4px solid $color; border-radius: 4px;'>";
            echo "<div style='display: flex; justify-content: between; align-items: start; margin-bottom: 5px;'>";
            echo "<strong style='color: $color; text-transform: capitalize;'>" . $notif['module_name'] . " " . $notif['action_type'] . "</strong>";
            echo "<span style='margin-left: auto; font-size: 12px; color: #6b7280;'>$readStatus</span>";
            echo "</div>";
            echo "<div style='color: #374151; margin-bottom: 5px;'>" . htmlspecialchars($notif['message']) . "</div>";
            echo "<small style='color: #6b7280;'>" . date('M j, Y H:i', strtotime($notif['created_at'])) . "</small>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
    
} catch(Exception $e) {
    echo "<div style='background: #fee2e2; border: 1px solid #dc2626; padding: 15px; border-radius: 8px;'>";
    echo "❌ Error loading notifications: " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🎯 Next Steps - Test Real Actions</h3>";
echo "<div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<p><strong>Now test these real controller actions to verify notifications work end-to-end:</strong></p>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;'>";

$actions = [
    ['Leave Request', '/ergon/leaves/create', '#3b82f6'],
    ['Expense Claim', '/ergon/expenses/create', '#10b981'],
    ['Advance Request', '/ergon/advances/create', '#8b5cf6'],
    ['Task Assignment', '/ergon/tasks/create', '#f59e0b'],
    ['Attendance Clock', '/ergon/attendance/clock', '#ef4444'],
    ['Daily Planner', '/ergon/planner', '#06b6d4'],
    ['Evening Update', '/ergon/evening-update', '#84cc16'],
    ['Follow-up', '/ergon/followups/create', '#f97316']
];

foreach($actions as $action) {
    echo "<a href='{$action[1]}' target='_blank' style='display: block; padding: 12px; background: {$action[2]}; color: white; text-decoration: none; border-radius: 6px; text-align: center; font-weight: 500;'>{$action[0]}</a>";
}

echo "</div>";
echo "<p style='margin-top: 15px;'><strong>After testing actions above:</strong></p>";
echo "<a href='/ergon/notifications' target='_blank' style='display: inline-block; padding: 12px 24px; background: #1f2937; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-right: 10px;'>🔔 View Owner Notifications</a>";
echo "<a href='/ergon/api/fetch_notifications.php' target='_blank' style='display: inline-block; padding: 12px 24px; background: #374151; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>📡 API Endpoint</a>";
echo "</div>";
?>