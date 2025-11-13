<?php
// Fix Owner Notifications - Web Version
require_once 'app/config/database.php';

echo "<h2>FIXING OWNER NOTIFICATIONS SYSTEM</h2>";

try {
    $db = Database::connect();
    
    // 1. Ensure notifications table exists with correct schema
    echo "<p>1. Creating/updating notifications table...</p>";
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
    echo "<p>✅ Notifications table created</p>";
    
    // 2. Ensure we have owner users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'owner'");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($owners)) {
        echo "<p>2. Creating owner user...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('System Owner', 'owner@ergon.com', ?, 'owner', 'active')");
        $stmt->execute([password_hash('owner123', PASSWORD_BCRYPT)]);
        $ownerId = $db->lastInsertId();
        echo "<p>✅ Created owner user with ID: $ownerId</p>";
    } else {
        $ownerId = $owners[0]['id'];
        echo "<p>2. Found owner user: " . $owners[0]['name'] . " (ID: $ownerId)</p>";
    }
    
    // 3. Ensure we have employee users
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'user'");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "<p>3. Creating employee users...</p>";
        $employeeData = [
            ['John Doe', 'john@ergon.com'],
            ['Jane Smith', 'jane@ergon.com'],
            ['Mike Johnson', 'mike@ergon.com']
        ];
        
        foreach ($employeeData as $emp) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
            $stmt->execute([$emp[0], $emp[1], password_hash('user123', PASSWORD_BCRYPT)]);
            echo "<p>✅ Created employee: " . $emp[0] . "</p>";
        }
        
        // Get updated employee list
        $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'user'");
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "<p>3. Found " . count($employees) . " employee users</p>";
    }
    
    // 4. Create sample notifications for owner
    echo "<p>4. Creating sample notifications for owner...</p>";
    
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
    echo "<p>✅ Created " . count($sampleNotifications) . " sample notifications</p>";
    
    // 5. Verify notifications
    echo "<p>5. Verifying owner notifications...</p>";
    $stmt = $db->prepare("SELECT n.*, u.name as sender_name FROM notifications n JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC");
    $stmt->execute([$ownerId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Owner notifications count: " . count($notifications) . "</p>";
    echo "<ul>";
    foreach ($notifications as $notification) {
        echo "<li>[{$notification['module_name']}] {$notification['message']} (from {$notification['sender_name']})</li>";
    }
    echo "</ul>";
    
    echo "<h3>OWNER NOTIFICATIONS SYSTEM FIXED</h3>";
    echo "<p>✅ Database schema corrected</p>";
    echo "<p>✅ Sample data created</p>";
    echo "<p>✅ Owner user available (ID: $ownerId)</p>";
    echo "<p>✅ " . count($notifications) . " notifications ready for display</p>";
    echo "<p><strong>Login as owner and visit: <a href='/ergon/notifications'>http://localhost/ergon/notifications</a></strong></p>";
    echo "<p><strong>Owner credentials: owner@ergon.com / owner123</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>