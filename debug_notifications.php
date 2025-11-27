<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Notification Debug Information</h2>";
    
    // Check if notifications table exists
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Notifications table exists</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $db->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
        
        // Count total notifications
        $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total notifications in database: <strong>{$total}</strong></p>";
        
        // Show recent notifications
        echo "<h3>Recent Notifications (Last 10):</h3>";
        $stmt = $db->query("SELECT n.*, u.name as sender_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id ORDER BY n.created_at DESC LIMIT 10");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            echo "<p>❌ No notifications found in database</p>";
        } else {
            echo "<table border='1' style='width:100%'>";
            echo "<tr><th>ID</th><th>Sender</th><th>Receiver ID</th><th>Title</th><th>Message</th><th>Category</th><th>Reference Type</th><th>Reference ID</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['sender_name']}</td>";
                echo "<td>{$notif['receiver_id']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
                echo "<td>{$notif['category']}</td>";
                echo "<td>{$notif['reference_type']}</td>";
                echo "<td>{$notif['reference_id']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check users and their roles
        echo "<h3>Users and Roles:</h3>";
        $stmt = $db->query("SELECT id, name, role, status FROM users ORDER BY role, name");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Status</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>{$user['name']}</td><td>{$user['role']}</td><td>{$user['status']}</td></tr>";
        }
        echo "</table>";
        
        // Check notifications by receiver role
        echo "<h3>Notifications by Receiver Role:</h3>";
        $stmt = $db->query("SELECT u.role, COUNT(n.id) as notification_count FROM notifications n JOIN users u ON n.receiver_id = u.id GROUP BY u.role");
        $roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>Role</th><th>Notification Count</th></tr>";
        foreach ($roleStats as $stat) {
            echo "<tr><td>{$stat['role']}</td><td>{$stat['notification_count']}</td></tr>";
        }
        echo "</table>";
        
        // Check approval category notifications specifically
        echo "<h3>Approval Category Notifications:</h3>";
        $stmt = $db->query("SELECT n.*, u.name as sender_name, ur.name as receiver_name, ur.role as receiver_role FROM notifications n LEFT JOIN users u ON n.sender_id = u.id LEFT JOIN users ur ON n.receiver_id = ur.id WHERE n.category = 'approval' ORDER BY n.created_at DESC");
        $approvalNotifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($approvalNotifs)) {
            echo "<p>❌ No approval notifications found</p>";
        } else {
            echo "<table border='1' style='width:100%'>";
            echo "<tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Receiver Role</th><th>Title</th><th>Message</th><th>Reference</th><th>Created</th></tr>";
            foreach ($approvalNotifs as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['sender_name']}</td>";
                echo "<td>{$notif['receiver_name']}</td>";
                echo "<td>{$notif['receiver_role']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
                echo "<td>{$notif['reference_type']}#{$notif['reference_id']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p>❌ Notifications table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>