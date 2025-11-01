<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class NotificationController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $title = 'Notifications';
        $active_page = 'notifications';
        
        // Check if all notifications should be marked as read
        $allRead = isset($_SESSION['notifications_all_read']) && $_SESSION['notifications_all_read'];
        
        // Simple mock data
        $notifications = [
            ['id' => 1, 'title' => 'New Task Assigned', 'message' => 'You have been assigned a new task', 'type' => 'info', 'is_read' => $allRead || (isset($_SESSION['notification_1_read']) && $_SESSION['notification_1_read']), 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'title' => 'Leave Approved', 'message' => 'Your leave request has been approved', 'type' => 'success', 'is_read' => $allRead || (isset($_SESSION['notification_2_read']) && $_SESSION['notification_2_read']), 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['id' => 3, 'title' => 'System Maintenance', 'message' => 'System will be under maintenance tonight', 'type' => 'warning', 'is_read' => true, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
        ];
        
        ob_start();
        include __DIR__ . '/../../views/notifications/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/dashboard.php';
    }
    
    public function getUnreadCount() {
        AuthMiddleware::requireAuth();
        
        header('Content-Type: application/json');
        echo json_encode(['count' => 2]);
        exit;
    }
    
    public function markAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            if ($id) {
                $_SESSION['notification_' . $id . '_read'] = true;
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            exit;
        }
    }
    
    public function markAllAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['notifications_all_read'] = true;
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            exit;
        }
    }
}