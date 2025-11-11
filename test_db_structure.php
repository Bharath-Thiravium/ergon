<?php
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h3>Notifications Table Structure:</h3>";
$result = $pdo->query("DESCRIBE notifications");
while ($row = $result->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

echo "<h3>Test Insert with sender_id/receiver_id:</h3>";
try {
    $stmt = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, user_id, title, message, module_name, action_type) VALUES (1, 2, 2, 'Test', 'Test message', 'tasks', 'created')");
    $stmt->execute();
    echo "✓ Insert successful with new columns<br>";
} catch(Exception $e) {
    echo "✗ Insert failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Notification Model:</h3>";
require_once 'app/models/Notification.php';
try {
    $notification = new Notification();
    $result = $notification->create([
        'sender_id' => 1,
        'receiver_id' => 2,
        'title' => 'Test Title',
        'module_name' => 'tasks',
        'action_type' => 'created',
        'message' => 'Test Message',
        'reference_id' => null
    ]);
    echo "✓ Notification model create works<br>";
} catch(Exception $e) {
    echo "✗ Notification model failed: " . $e->getMessage() . "<br>";
}
?>