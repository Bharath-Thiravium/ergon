<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'mark-read':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                $_SESSION['notification_' . $id . '_read'] = true;
            }
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            break;
            
        case 'mark-all-read':
            $_SESSION['notifications_all_read'] = true;
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            break;
            
        case 'unread-count':
            $count = 0;
            if (!isset($_SESSION['notifications_all_read'])) {
                $count = 2; // Mock count
            }
            echo json_encode(['count' => $count]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>