<?php
require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Notification.php';

Session::init();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ergon/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification = new Notification();
    
    $created = $notification->create([
        'sender_id' => 1,
        'receiver_id' => $_SESSION['user_id'],
        'type' => 'info',
        'category' => 'system',
        'title' => 'Test Notification',
        'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
        'reference_type' => 'task',
        'reference_id' => 1
    ]);
    
    if ($created) {
        header('Location: /ergon/notifications?created=1');
    } else {
        header('Location: /ergon/notifications?error=1');
    }
    exit;
}

header('Location: /ergon/dashboard');
exit;
?>