<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';
require_once __DIR__ . '/app/core/Session.php';

Session::init();

// Test notification system
echo "<h2>Notification System Test</h2>";

try {
    $notification = new Notification();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<p>❌ No user logged in. Please log in first.</p>";
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    echo "<p>✅ Testing for user ID: {$userId}</p>";
    
    // Create a test notification
    $testData = [
        'sender_id' => 1,
        'receiver_id' => $userId,
        'type' => 'info',
        'category' => 'system',
        'title' => 'Test Notification',
        'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
        'reference_type' => 'task',
        'reference_id' => 1,
        'priority' => 1
    ];
    
    $created = $notification->create($testData);
    if ($created) {
        echo "<p>✅ Test notification created successfully</p>";
    } else {
        echo "<p>❌ Failed to create test notification</p>";
    }
    
    // Get notifications for user
    $notifications = $notification->getForUser($userId, 10);
    echo "<p>Found " . count($notifications) . " notifications for user</p>";
    
    if (!empty($notifications)) {
        echo "<h3>Recent Notifications:</h3>";
        echo "<table border='1' style='width:100%'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Category</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            $readStatus = $notif['is_read'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['category']}</td>";
            echo "<td>{$readStatus}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Get unread count
    $unreadCount = $notification->getUnreadCount($userId);
    echo "<p>Unread notifications: <strong>{$unreadCount}</strong></p>";
    
    // Test API endpoint
    echo "<h3>API Test:</h3>";
    $apiUrl = '/ergon/api/notifications.php';
    echo "<p>Testing API endpoint: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
    
    // Simulate API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest',
        'Cache-Control: no-cache'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>API Response Code: {$httpCode}</p>";
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "<p>✅ API returned valid JSON</p>";
            echo "<p>Success: " . ($data['success'] ? 'Yes' : 'No') . "</p>";
            echo "<p>Notification count: " . count($data['notifications'] ?? []) . "</p>";
            echo "<p>Unread count: " . ($data['unread_count'] ?? 0) . "</p>";
        } else {
            echo "<p>❌ API returned invalid JSON</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    } else {
        echo "<p>❌ No response from API</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<script>
// Test JavaScript notification loading
console.log('Testing notification loading...');

fetch('/ergon/api/notifications.php?t=' + Date.now(), {
    method: 'GET',
    headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
    console.log('Notification API Response:', data);
    if (data.success) {
        console.log('✅ Notifications loaded successfully');
        console.log('Count:', data.notifications.length);
        console.log('Unread:', data.unread_count);
    } else {
        console.log('❌ API returned error:', data.error);
    }
})
.catch(error => {
    console.log('❌ Fetch error:', error);
});
</script>