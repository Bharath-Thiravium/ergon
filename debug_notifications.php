<?php
session_start();
echo "<h1>Debug Notifications</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .info{color:blue;} .error{color:red;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    echo "<h2>Session Info</h2>";
    echo "<div class='info'>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</div>";
    echo "<div class='info'>Role: " . ($_SESSION['role'] ?? 'Not set') . "</div>";
    
    echo "<h2>All Notifications</h2>";
    $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'>Total notifications: " . count($all) . "</div>";
    foreach ($all as $n) {
        echo "<div>ID: {$n['id']}, Sender: {$n['sender_id']}, Receiver: {$n['receiver_id']}, Message: {$n['message']}</div>";
    }
    
    echo "<h2>Owner Users</h2>";
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'owner'");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($owners as $owner) {
        echo "<div>Owner ID: {$owner['id']}, Name: {$owner['name']}</div>";
    }
    
    // Fix session to match owner
    if (!empty($owners)) {
        $_SESSION['user_id'] = $owners[0]['id'];
        $_SESSION['role'] = 'owner';
        echo "<div class='info'>Fixed session to owner ID: {$owners[0]['id']}</div>";
    }
    
    echo "<p><a href='/ergon/notifications'>View Notifications</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>