<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Get current user ID from session or use default
    if (session_status() === PHP_SESSION_NONE) session_start();
    $currentUserId = $_SESSION['user_id'] ?? 1;
    
    // Clear existing notifications
    $db->exec("DELETE FROM notifications");
    
    // Create test notifications for current user
    $notifications = [
        [
            'sender_id' => 1,
            'receiver_id' => $currentUserId,
            'type' => 'info',
            'category' => 'approval',
            'title' => 'New Leave Request',
            'message' => 'Leave request for Annual Leave (Dec 25-27) needs approval',
            'reference_type' => 'leave',
            'reference_id' => 1,
            'action_url' => '/ergon/leaves'
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $currentUserId,
            'type' => 'info',
            'category' => 'approval',
            'title' => 'New Expense Request',
            'message' => 'Expense request for $250.00 office supplies needs approval',
            'reference_type' => 'expense',
            'reference_id' => 1,
            'action_url' => '/ergon/expenses'
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $currentUserId,
            'type' => 'success',
            'category' => 'approval',
            'title' => 'Leave Request Approved',
            'message' => 'Your leave request has been approved',
            'reference_type' => 'leave',
            'reference_id' => 2,
            'action_url' => '/ergon/leaves'
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $currentUserId,
            'type' => 'info',
            'category' => 'task',
            'title' => 'Task Assignment',
            'message' => 'You have been assigned: Update Customer Database',
            'reference_type' => 'task',
            'reference_id' => 1,
            'action_url' => '/ergon/tasks'
        ],
        [
            'sender_id' => 1,
            'receiver_id' => $currentUserId,
            'type' => 'warning',
            'category' => 'reminder',
            'title' => 'Task Reminder',
            'message' => 'Task "Complete Documentation" is due tomorrow',
            'reference_type' => 'task',
            'reference_id' => 2,
            'action_url' => '/ergon/tasks'
        ]
    ];
    
    $stmt = $db->prepare("INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, action_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($notifications as $notif) {
        $stmt->execute([
            $notif['sender_id'],
            $notif['receiver_id'],
            $notif['type'],
            $notif['category'],
            $notif['title'],
            $notif['message'],
            $notif['reference_type'],
            $notif['reference_id'],
            $notif['action_url']
        ]);
    }
    
    echo "✅ Created " . count($notifications) . " test notifications for user ID: {$currentUserId}<br>";
    echo "<a href='/ergon/notifications'>View Notifications</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>