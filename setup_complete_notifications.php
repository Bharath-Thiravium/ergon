<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔔 Complete Notification System Setup</h2>";
    
    // 1. Ensure notifications table has all required columns
    $columns = [
        'sender_id' => 'INT NOT NULL',
        'receiver_id' => 'INT NOT NULL', 
        'type' => "ENUM('info', 'success', 'warning', 'error', 'urgent') DEFAULT 'info'",
        'category' => "ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system'",
        'title' => 'VARCHAR(255) NOT NULL',
        'message' => 'TEXT NOT NULL',
        'action_url' => 'VARCHAR(500) DEFAULT NULL',
        'reference_type' => 'VARCHAR(50) DEFAULT NULL',
        'reference_id' => 'INT DEFAULT NULL',
        'priority' => 'TINYINT(1) DEFAULT 1',
        'is_read' => 'BOOLEAN DEFAULT FALSE',
        'read_at' => 'TIMESTAMP NULL DEFAULT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            $db->exec("ALTER TABLE notifications ADD COLUMN {$column} {$definition}");
            echo "✅ Added column: {$column}<br>";
        } catch (Exception $e) {
            // Column exists, continue
        }
    }
    
    // 2. Create test notifications for all roles
    $notification = new Notification();
    
    // Get users by role
    $owners = $db->query("SELECT id, name FROM users WHERE role = 'owner' LIMIT 1")->fetchAll();
    $admins = $db->query("SELECT id, name FROM users WHERE role = 'admin' LIMIT 1")->fetchAll();
    $users = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1")->fetchAll();
    
    if (empty($owners) || empty($admins) || empty($users)) {
        echo "<p>❌ Missing required user roles. Please ensure you have owner, admin, and user accounts.</p>";
        exit;
    }
    
    $owner = $owners[0];
    $admin = $admins[0];
    $user = $users[0];
    
    echo "<h3>Creating comprehensive test notifications...</h3>";
    
    // Owner Panel Notifications
    echo "<h4>Owner Panel:</h4>";
    
    // Leave approval notifications for Owner
    $notification->create([
        'sender_id' => $user['id'],
        'receiver_id' => $owner['id'],
        'type' => 'info',
        'category' => 'approval',
        'title' => 'New Leave Request',
        'message' => "Leave request from {$user['name']} for Annual Leave (3 days)",
        'reference_type' => 'leave',
        'reference_id' => 1,
        'action_url' => '/ergon/leaves'
    ]);
    
    // Expense approval notifications for Owner
    $notification->create([
        'sender_id' => $user['id'],
        'receiver_id' => $owner['id'],
        'type' => 'info',
        'category' => 'approval',
        'title' => 'New Expense Request',
        'message' => "Expense request from {$user['name']} - $250.00 for office supplies",
        'reference_type' => 'expense',
        'reference_id' => 1,
        'action_url' => '/ergon/expenses'
    ]);
    
    // Advance approval notifications for Owner
    $notification->create([
        'sender_id' => $user['id'],
        'receiver_id' => $owner['id'],
        'type' => 'info',
        'category' => 'approval',
        'title' => 'New Advance Request',
        'message' => "Advance request from {$user['name']} - $500.00 for emergency",
        'reference_type' => 'advance',
        'reference_id' => 1,
        'action_url' => '/ergon/advances'
    ]);
    
    // Task assignment from Admin to Owner
    $notification->create([
        'sender_id' => $admin['id'],
        'receiver_id' => $owner['id'],
        'type' => 'info',
        'category' => 'task',
        'title' => 'New Task Assigned',
        'message' => "You have been assigned: Review Monthly Financial Reports",
        'reference_type' => 'task',
        'reference_id' => 1,
        'action_url' => '/ergon/tasks'
    ]);
    
    echo "✅ Owner notifications created<br>";
    
    // Admin Panel Notifications
    echo "<h4>Admin Panel:</h4>";
    
    // Leave approval notifications for Admin
    $notification->create([
        'sender_id' => $user['id'],
        'receiver_id' => $admin['id'],
        'type' => 'info',
        'category' => 'approval',
        'title' => 'New Leave Request',
        'message' => "Leave request from {$user['name']} for Sick Leave (2 days)",
        'reference_type' => 'leave',
        'reference_id' => 2,
        'action_url' => '/ergon/leaves'
    ]);
    
    // Expense approval notifications for Admin
    $notification->create([
        'sender_id' => $user['id'],
        'receiver_id' => $admin['id'],
        'type' => 'info',
        'category' => 'approval',
        'title' => 'New Expense Request',
        'message' => "Expense request from {$user['name']} - $120.00 for travel",
        'reference_type' => 'expense',
        'reference_id' => 2,
        'action_url' => '/ergon/expenses'
    ]);
    
    // Task assignment from Owner to Admin
    $notification->create([
        'sender_id' => $owner['id'],
        'receiver_id' => $admin['id'],
        'type' => 'info',
        'category' => 'task',
        'title' => 'New Task Assigned',
        'message' => "You have been assigned: Prepare Team Performance Report",
        'reference_type' => 'task',
        'reference_id' => 2,
        'action_url' => '/ergon/tasks'
    ]);
    
    // Approved request notification for Admin
    $notification->create([
        'sender_id' => $owner['id'],
        'receiver_id' => $admin['id'],
        'type' => 'success',
        'category' => 'approval',
        'title' => 'Expense Request Approved',
        'message' => "Your expense request has been approved by {$owner['name']}",
        'reference_type' => 'expense',
        'reference_id' => 3,
        'action_url' => '/ergon/expenses'
    ]);
    
    echo "✅ Admin notifications created<br>";
    
    // User Panel Notifications
    echo "<h4>User Panel:</h4>";
    
    // Approved leave notification
    $notification->create([
        'sender_id' => $admin['id'],
        'receiver_id' => $user['id'],
        'type' => 'success',
        'category' => 'approval',
        'title' => 'Leave Request Approved',
        'message' => "Your leave request has been approved by {$admin['name']}",
        'reference_type' => 'leave',
        'reference_id' => 1,
        'action_url' => '/ergon/leaves'
    ]);
    
    // Rejected expense notification
    $notification->create([
        'sender_id' => $admin['id'],
        'receiver_id' => $user['id'],
        'type' => 'warning',
        'category' => 'approval',
        'title' => 'Expense Request Rejected',
        'message' => "Your expense request has been rejected. Please review and resubmit.",
        'reference_type' => 'expense',
        'reference_id' => 2,
        'action_url' => '/ergon/expenses'
    ]);
    
    // Task assignment notifications
    $notification->create([
        'sender_id' => $admin['id'],
        'receiver_id' => $user['id'],
        'type' => 'info',
        'category' => 'task',
        'title' => 'New Task Assigned',
        'message' => "You have been assigned: Update Customer Database",
        'reference_type' => 'task',
        'reference_id' => 3,
        'action_url' => '/ergon/tasks'
    ]);
    
    // Task reminder notification
    $notification->create([
        'sender_id' => 1,
        'receiver_id' => $user['id'],
        'type' => 'warning',
        'category' => 'reminder',
        'title' => 'Task Reminder',
        'message' => "Task 'Complete Documentation' is due tomorrow",
        'reference_type' => 'task',
        'reference_id' => 4,
        'action_url' => '/ergon/tasks',
        'priority' => 2
    ]);
    
    echo "✅ User notifications created<br>";
    
    // 3. Test notification counts
    $ownerCount = $notification->getUnreadCount($owner['id']);
    $adminCount = $notification->getUnreadCount($admin['id']);
    $userCount = $notification->getUnreadCount($user['id']);
    
    echo "<h3>Notification Counts:</h3>";
    echo "<ul>";
    echo "<li>Owner ({$owner['name']}): {$ownerCount} unread</li>";
    echo "<li>Admin ({$admin['name']}): {$adminCount} unread</li>";
    echo "<li>User ({$user['name']}): {$userCount} unread</li>";
    echo "</ul>";
    
    echo "<h3>✅ Complete Notification System Ready!</h3>";
    echo "<p><strong>Features Implemented:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Owner Panel: Approval notifications for Leave, Expense, Advance requests</li>";
    echo "<li>✅ Owner Panel: Task assignment notifications from Admin</li>";
    echo "<li>✅ Admin Panel: Approval notifications for Leave, Expense, Advance requests</li>";
    echo "<li>✅ Admin Panel: Task assignment notifications from Owner</li>";
    echo "<li>✅ Admin Panel: Approved/rejected request notifications</li>";
    echo "<li>✅ User Panel: Approved/rejected notifications for all requests</li>";
    echo "<li>✅ User Panel: Task assignment notifications</li>";
    echo "<li>✅ User Panel: Task reminder notifications</li>";
    echo "<li>✅ Real-time notification badge updates</li>";
    echo "<li>✅ Notification dropdown with recent items</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the notification button in the header</li>";
    echo "<li><a href='/ergon/notifications'>Visit Notifications Page</a></li>";
    echo "<li>Create actual requests to test live notifications</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>