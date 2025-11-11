<?php
// Test script to verify notification system
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';

try {
    $db = Database::connect();
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        module_name VARCHAR(50) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        reference_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_receiver_read (receiver_id, is_read),
        INDEX idx_created_at (created_at)
    )";
    $db->exec($sql);
    echo "✅ Notifications table created/verified\n";
    
    // Get owner user ID
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
    $stmt->execute();
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$owner) {
        echo "❌ No owner user found\n";
        exit;
    }
    
    $ownerId = $owner['id'];
    echo "✅ Owner ID: $ownerId\n";
    
    // Create test notifications
    $notification = new Notification();
    
    $testNotifications = [
        [
            'sender_id' => 1,
            'receiver_id' => $ownerId,
            'module_name' => 'leave',
            'action_type' => 'request',
            'message' => 'Test leave request notification',
            'reference_id' => 1
        ],
        [
            'sender_id' => 2,
            'receiver_id' => $ownerId,
            'module_name' => 'expense',
            'action_type' => 'claim',
            'message' => 'Test expense claim notification',
            'reference_id' => 1
        ]
    ];
    
    foreach ($testNotifications as $data) {
        $result = $notification->create($data);
        if ($result) {
            echo "✅ Created notification: {$data['message']}\n";
        } else {
            echo "❌ Failed to create notification: {$data['message']}\n";
        }
    }
    
    // Test retrieval
    $notifications = $notification->getForUser($ownerId);
    echo "✅ Retrieved " . count($notifications) . " notifications for owner\n";
    
    foreach ($notifications as $notif) {
        echo "  - {$notif['message']} (ID: {$notif['id']})\n";
    }
    
    // Test unread count
    $unreadCount = $notification->getUnreadCount($ownerId);
    echo "✅ Unread count: $unreadCount\n";
    
    echo "\n🎉 Notification system test completed successfully!\n";
    echo "Now visit: http://localhost/ergon/notifications\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>