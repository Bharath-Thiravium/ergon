<?php
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>Fix Database and Test Notifications</h2>";

// 1. Fix leaves table
echo "<h3>1. Fix Leaves Table</h3>";
try {
    $pdo->exec("ALTER TABLE leaves ADD COLUMN days_requested INT DEFAULT 1");
    echo "✓ Added days_requested column to leaves<br>";
} catch(Exception $e) {
    echo "days_requested column exists<br>";
}

// 2. Remove foreign key constraint from expenses
echo "<h3>2. Fix Expenses Table</h3>";
try {
    $pdo->exec("ALTER TABLE expenses DROP FOREIGN KEY expenses_ibfk_1");
    echo "✓ Removed foreign key constraint from expenses<br>";
} catch(Exception $e) {
    echo "Foreign key constraint doesn't exist<br>";
}

// 3. Create test users if they don't exist
echo "<h3>3. Create Test Users</h3>";
$users = [
    [1, 'System Owner', 'owner@ergon.com', 'owner'],
    [2, 'John Employee', 'john@ergon.com', 'user'], 
    [3, 'Jane Employee', 'jane@ergon.com', 'user'],
    [4, 'Mike Employee', 'mike@ergon.com', 'user']
];

foreach($users as $user) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user[0], $user[1], $user[2], password_hash('password', PASSWORD_BCRYPT), $user[3]]);
        echo "✓ User {$user[1]} created/exists<br>";
    } catch(Exception $e) {
        echo "User {$user[1]} error: " . $e->getMessage() . "<br>";
    }
}

// 4. Test notification creation
echo "<h3>4. Test Direct Notification Creation</h3>";
require_once 'app/models/Notification.php';

try {
    $notification = new Notification();
    
    // Test 1: Leave notification
    $result1 = $notification->create([
        'sender_id' => 2,
        'receiver_id' => 1,
        'title' => 'Leave Request',
        'module_name' => 'leave',
        'action_type' => 'request',
        'message' => 'John Employee has requested leave from 2024-01-20 to 2024-01-22',
        'reference_id' => 1
    ]);
    echo $result1 ? "✓ Leave notification created<br>" : "✗ Leave notification failed<br>";
    
    // Test 2: Expense notification  
    $result2 = $notification->create([
        'sender_id' => 3,
        'receiver_id' => 1,
        'title' => 'Expense Claim',
        'module_name' => 'expense', 
        'action_type' => 'claim',
        'message' => 'Jane Employee submitted expense claim of ₹750 for Travel',
        'reference_id' => 2
    ]);
    echo $result2 ? "✓ Expense notification created<br>" : "✗ Expense notification failed<br>";
    
    // Test 3: Task notification
    $result3 = $notification->create([
        'sender_id' => 1,
        'receiver_id' => 4,
        'title' => 'Task Assignment',
        'module_name' => 'task',
        'action_type' => 'assigned', 
        'message' => 'You have been assigned a new task: Database Setup',
        'reference_id' => 3
    ]);
    echo $result3 ? "✓ Task notification created<br>" : "✗ Task notification failed<br>";
    
} catch(Exception $e) {
    echo "✗ Notification error: " . $e->getMessage() . "<br>";
}

// 5. Check notifications for owner
echo "<h3>5. Check Owner Notifications</h3>";
try {
    $notifications = $notification->getForUser(1);
    echo "Total notifications for owner: " . count($notifications) . "<br>";
    
    foreach($notifications as $notif) {
        echo "- {$notif['message']} <small>({$notif['module_name']}/{$notif['action_type']})</small><br>";
    }
    
} catch(Exception $e) {
    echo "✗ Error getting notifications: " . $e->getMessage() . "<br>";
}

// 6. Test notification triggers in controllers
echo "<h3>6. Test Controller Integration</h3>";
echo "Now test these actions to see if notifications are created:<br>";
echo "• <a href='/ergon/leaves/create' target='_blank'>Create Leave Request</a><br>";
echo "• <a href='/ergon/expenses/create' target='_blank'>Create Expense Claim</a><br>";
echo "• <a href='/ergon/tasks/create' target='_blank'>Create Task</a><br>";
echo "<br>After creating items, check: <a href='/ergon/notifications' target='_blank'>Notifications Page</a>";
?>