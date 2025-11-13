<?php
// Fix Owner Notifications - Complete Database Setup and Test Data
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== FIXING OWNER NOTIFICATIONS SYSTEM ===\n\n";
    
    // 1. Ensure notifications table exists with correct schema
    echo "1. Creating/updating notifications table...\n";
    $db->exec("DROP TABLE IF EXISTS notifications");
    $db->exec("CREATE TABLE notifications (
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
    )");
    echo "✅ Notifications table created\n";
    
    // 2. Ensure we have owner users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'owner'");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($owners)) {
        echo "2. Creating owner user...\n";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('System Owner', 'owner@ergon.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('owner123', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "✅ Created owner user with ID: $ownerId\n";
    } else {
        $ownerId = $owners[0]['id'];
        echo "2. Found owner user: " . $owners[0]['name'] . " (ID: $ownerId)\n";
    }
    
    // 3. Ensure we have employee users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'user'");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "3. Creating employee users...\n";
        $employeeData = [
            ['John Doe', 'john@ergon.com'],
            ['Jane Smith', 'jane@ergon.com'],
            ['Mike Johnson', 'mike@ergon.com']
        ];
        
        foreach ($employeeData as $emp) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
            $stmt->execute([$emp[0], $emp[1], password_hash('user123', PASSWORD_BCRYPT)]);
            echo "✅ Created employee: " . $emp[0] . "\n";
        }
        
        // Get updated employee list
        $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'user'");
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "3. Found " . count($employees) . " employee users\n";
    }
    
    // 4. Create sample notifications for owner
    echo "4. Creating sample notifications for owner...\n";
    
    $sampleNotifications = [
        [
            'sender_id' => $employees[0]['id'] ?? 2,
            'receiver_id' => $ownerId,
            'module_name' => 'leave',
            'action_type' => 'request',
            'message' => ($employees[0]['name'] ?? 'John Doe') . ' has submitted a leave request for approval',
            'reference_id' => 1
        ],
        [
            'sender_id' => $employees[1]['id'] ?? 3,
            'receiver_id' => $ownerId,
            'module_name' => 'expense',
            'action_type' => 'claim',
            'message' => ($employees[1]['name'] ?? 'Jane Smith') . ' submitted expense claim of ₹500 for travel',
            'reference_id' => 2
        ],
        [
            'sender_id' => $employees[2]['id'] ?? 4,
            'receiver_id' => $ownerId,
            'module_name' => 'task',
            'action_type' => 'created',
            'message' => 'New task "Database Setup" has been created by ' . ($employees[2]['name'] ?? 'Mike Johnson'),
            'reference_id' => 3
        ],
        [
            'sender_id' => $employees[0]['id'] ?? 2,
            'receiver_id' => $ownerId,
            'module_name' => 'attendance',
            'action_type' => 'late_arrival',
            'message' => ($employees[0]['name'] ?? 'John Doe') . ' arrived late at 10:30 AM',
            'reference_id' => 4
        ],
        [
            'sender_id' => $employees[1]['id'] ?? 3,
            'receiver_id' => $ownerId,
            'module_name' => 'advance',
            'action_type' => 'request',
            'message' => ($employees[1]['name'] ?? 'Jane Smith') . ' requested salary advance of ₹10000',
            'reference_id' => 5
        ]
    ];
    
    foreach ($sampleNotifications as $notification) {
        $stmt = $db->prepare("INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $notification['sender_id'],
            $notification['receiver_id'],
            $notification['module_name'],
            $notification['action_type'],
            $notification['message'],
            $notification['reference_id']
        ]);
    }
    echo "✅ Created " . count($sampleNotifications) . " sample notifications\n";
    
    // 5. Verify notifications
    echo "5. Verifying owner notifications...\n";
    $stmt = $db->prepare("SELECT n.*, u.name as sender_name FROM notifications n JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC");
    $stmt->execute([$ownerId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Owner notifications count: " . count($notifications) . "\n";
    foreach ($notifications as $notification) {
        echo "- [{$notification['module_name']}] {$notification['message']} (from {$notification['sender_name']})\n";
    }
    
    echo "\n=== OWNER NOTIFICATIONS SYSTEM FIXED ===\n";
    echo "✅ Database schema corrected\n";
    echo "✅ Sample data created\n";
    echo "✅ Owner user available (ID: $ownerId)\n";
    echo "✅ " . count($notifications) . " notifications ready for display\n";
    echo "\nLogin as owner and visit: http://localhost/ergon/notifications\n";
    echo "Owner credentials: owner@ergon.com / owner123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>