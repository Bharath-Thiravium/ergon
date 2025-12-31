<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class NotificationController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            require_once __DIR__ . '/../models/Notification.php';
            $notificationModel = new Notification();
            
            // Debug: Check if notifications exist in database
            $db = Database::connect();
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ?");
            $stmt->execute([$userId]);
            $totalCount = $stmt->fetchColumn();
            
            $notifications = $notificationModel->getForUser($userId);
            
            // Debug output
            error_log("User ID: {$userId}, Total notifications in DB: {$totalCount}, Fetched: " . count($notifications));
            
            $data = [
                'notifications' => $notifications,
                'user_role' => $role,
                'active_page' => 'notifications',
                'debug_info' => "User: {$userId}, DB Count: {$totalCount}, Fetched: " . count($notifications)
            ];
            
            $this->view('notifications/index', $data);
        } catch (Exception $e) {
            error_log('Notification controller error: ' . $e->getMessage());
            $data = [
                'notifications' => [],
                'user_role' => $_SESSION['role'],
                'active_page' => 'notifications',
                'error' => 'Unable to load notifications: ' . $e->getMessage()
            ];
            $this->view('notifications/index', $data);
        }
    }
    
    public function getUnreadCount() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../models/Notification.php';
            $notificationModel = new Notification();
            $count = $notificationModel->getUnreadCount($_SESSION['user_id']);
            
            header('Content-Type: application/json');
            echo json_encode(['count' => $count]);
        } catch (Exception $e) {
            error_log('Unread count error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['count' => 0]);
        }
        exit;
    }
    
    public function markAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../models/Notification.php';
                $notificationModel = new Notification();
                
                $id = $_POST['id'] ?? 0;
                $success = false;
                
                if ($id) {
                    $success = $notificationModel->markAsRead($id, $_SESSION['user_id']);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => $success, 'message' => $success ? 'Notification marked as read' : 'Failed to mark as read']);
            } catch (Exception $e) {
                error_log('Mark as read error: ' . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    public function markAllAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../models/Notification.php';
                $notificationModel = new Notification();
                
                $success = $notificationModel->markAllAsRead($_SESSION['user_id']);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => $success, 'message' => $success ? 'All notifications marked as read' : 'Failed to mark all as read']);
            } catch (Exception $e) {
                error_log('Mark all as read error: ' . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
}
?>
