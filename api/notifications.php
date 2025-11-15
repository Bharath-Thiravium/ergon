<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    require_once __DIR__ . '/../app/core/Session.php';
    Session::init();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/models/Notification.php';
    
    $notification = new Notification();
    $userId = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'mark-read':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $result = $notification->markAsRead($id, $userId);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                }
                break;
                
            case 'mark-all-read':
                $result = $notification->markAllAsRead($userId);
                echo json_encode(['success' => $result]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } else {
        $notifications = $notification->getForUser($userId, 10);
        $unreadCount = $notification->getUnreadCount($userId);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>