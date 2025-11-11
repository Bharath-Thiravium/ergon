<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>Complete Notification System Test</h2>";

// Set owner session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

// Clear existing notifications for clean test
$pdo->exec("DELETE FROM notifications WHERE receiver_id = 1");
echo "✓ Cleared existing notifications<br><br>";

echo "<h3>Testing All Module Notifications</h3>";

require_once 'app/helpers/NotificationHelper.php';

// 1. Leave Request Notification
echo "<strong>1. Leave Request:</strong> ";
try {
    NotificationHelper::notifyOwners(2, 'leave', 'request', 'John Employee requested leave from 2024-01-20 to 2024-01-22', 1);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 2. Expense Claim Notification  
echo "<strong>2. Expense Claim:</strong> ";
try {
    NotificationHelper::notifyOwners(3, 'expense', 'claim', 'Jane Employee submitted expense claim of ₹750 for Travel', 2);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 3. Task Assignment Notification
echo "<strong>3. Task Assignment:</strong> ";
try {
    NotificationHelper::notifyOwners(1, 'task', 'created', 'New task "Database Setup" assigned to Mike Employee', 3);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 4. Late Attendance Notification
echo "<strong>4. Late Attendance:</strong> ";
try {
    NotificationHelper::notifyOwners(4, 'attendance', 'late_arrival', 'Mike Employee arrived late at 10:15', 4);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 5. Advance Request Notification
echo "<strong>5. Advance Request:</strong> ";
try {
    NotificationHelper::notifyOwners(2, 'advance', 'request', 'John Employee requested advance of ₹5000 for emergency', 5);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 6. Daily Plan Submission
echo "<strong>6. Daily Plan:</strong> ";
try {
    NotificationHelper::notifyOwners(3, 'planner', 'submitted', 'Jane Employee submitted daily plan for today', 6);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 7. Evening Update
echo "<strong>7. Evening Update:</strong> ";
try {
    NotificationHelper::notifyOwners(4, 'evening_update', 'submitted', 'Mike Employee submitted evening update with 85% productivity', 7);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// 8. Follow-up Created
echo "<strong>8. Follow-up:</strong> ";
try {
    NotificationHelper::notifyOwners(2, 'followup', 'created', 'John Employee created follow-up for ABC Client meeting', 8);
    echo "✓ Created<br>";
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Verification - Owner Notifications</h3>";
try {
    require_once 'app/models/Notification.php';
    $notification = new Notification();
    $notifications = $notification->getForUser(1);
    
    echo "Total notifications for owner: <strong>" . count($notifications) . "</strong><br><br>";
    
    if (count($notifications) > 0) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        foreach($notifications as $notif) {
            $moduleColor = [
                'leave' => '#3b82f6',
                'expense' => '#10b981', 
                'task' => '#f59e0b',
                'attendance' => '#ef4444',
                'advance' => '#8b5cf6',
                'planner' => '#06b6d4',
                'evening_update' => '#84cc16',
                'followup' => '#f97316'
            ];
            $color = $moduleColor[$notif['module_name']] ?? '#6b7280';
            
            echo "<div style='margin: 8px 0; padding: 10px; background: white; border-left: 4px solid $color; border-radius: 4px;'>";
            echo "<strong style='color: $color;'>" . ucfirst($notif['module_name']) . " " . ucfirst($notif['action_type']) . "</strong><br>";
            echo "<span style='color: #374151;'>{$notif['message']}</span><br>";
            echo "<small style='color: #6b7280;'>" . date('M j, Y H:i', strtotime($notif['created_at'])) . "</small>";
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch(Exception $e) {
    echo "✗ Error getting notifications: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Test Real Controller Actions</h3>";
echo "<p>Now test these actual controller actions to verify notifications are triggered:</p>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;'>";
echo "<a href='/ergon/leaves/create' target='_blank' style='padding: 10px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; text-align: center;'>Create Leave Request</a>";
echo "<a href='/ergon/expenses/create' target='_blank' style='padding: 10px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; text-align: center;'>Create Expense</a>";
echo "<a href='/ergon/tasks/create' target='_blank' style='padding: 10px; background: #f59e0b; color: white; text-decoration: none; border-radius: 6px; text-align: center;'>Create Task</a>";
echo "<a href='/ergon/attendance/clock' target='_blank' style='padding: 10px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; text-align: center;'>Clock In/Out</a>";
echo "</div>";

echo "<br><p><strong>After testing above actions, check:</strong> <a href='/ergon/notifications' target='_blank' style='color: #3b82f6; font-weight: bold;'>Owner Notifications Page</a></p>";
?>