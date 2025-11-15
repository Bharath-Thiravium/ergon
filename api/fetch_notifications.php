<?php
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

Session::init();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
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
    error_log('Fetch Notifications Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>