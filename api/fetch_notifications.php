<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $notification = new Notification();
    $notifications = $notification->getForUser($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $notification->getUnreadCount($_SESSION['user_id'])
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>