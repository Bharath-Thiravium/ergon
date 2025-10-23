<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class NotificationController {
    private $notificationModel;
    
    public function __construct() {
        AuthMiddleware::requireAuth();
        $this->notificationModel = new Notification();
    }
    
    public function index() {
        $notifications = $this->notificationModel->getUserNotifications($_SESSION['user_id'], 20);
        $data = ['notifications' => $notifications];
        include __DIR__ . '/../views/notifications/index.php';
    }
    
    public function getUnreadCount() {
        header('Content-Type: application/json');
        $count = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        echo json_encode(['count' => $count]);
        exit;
    }
    
    public function markAsRead() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationId = $input['notification_id'] ?? 0;
        
        $result = $this->notificationModel->markAsRead($notificationId, $_SESSION['user_id']);
        echo json_encode(['success' => $result]);
        exit;
    }
}
?>