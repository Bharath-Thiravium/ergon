<?php
if (session_status() === PHP_SESSION_NONE) session_start();

echo "<h2>👤 Current User Information</h2>";

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/app/config/database.php';
    
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p><strong>User ID:</strong> {$user['id']}</p>";
            echo "<p><strong>Name:</strong> {$user['name']}</p>";
            echo "<p><strong>Email:</strong> {$user['email']}</p>";
            echo "<p><strong>Role:</strong> {$user['role']}</p>";
            
            // Check notifications for this user
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ?");
            $stmt->execute([$user['id']]);
            $notificationCount = $stmt->fetchColumn();
            
            echo "<p><strong>Notifications for this user:</strong> {$notificationCount}</p>";
            
            echo "<p>The notifications at <a href='/ergon/notifications'>/ergon/notifications</a> are showing for this user.</p>";
            
        } else {
            echo "<p>❌ User not found in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ No user logged in</p>";
    echo "<p><a href='/ergon/login'>Login here</a></p>";
}
?>