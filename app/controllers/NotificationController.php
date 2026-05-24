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
        session_write_close(); // Release session lock for concurrent requests
        
        try {
            require_once __DIR__ . '/../models/Notification.php';
            $notificationModel = new Notification();
            $userId = $_SESSION['user_id'];
            $count = $notificationModel->getUnreadCount($userId);
            $notifications = $notificationModel->getForDropdown($userId, 10);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => $count,
                'unread_count' => $count,
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            error_log('Unread count error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => 0, 'unread_count' => 0, 'notifications' => []]);
        }
        exit;
    }
    
    public function pushSubscribe() {
        AuthMiddleware::requireAuth();
        header('Content-Type: application/json');

        $input  = json_decode(file_get_contents('php://input'), true);
        $type   = $input['type'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        try {
            $db = Database::connect();
            $db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type ENUM('web','fcm') NOT NULL DEFAULT 'web',
                endpoint TEXT,
                p256dh VARCHAR(255),
                auth VARCHAR(255),
                fcm_token TEXT,
                device_info VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            if ($type === 'web') {
                $endpoint = $input['endpoint'] ?? '';
                $p256dh   = $input['keys']['p256dh'] ?? '';
                $auth     = $input['keys']['auth'] ?? '';
                if (!$endpoint) { echo json_encode(['success' => false]); exit; }

                $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id=? AND type='web' AND endpoint=?");
                $stmt->execute([$userId, $endpoint]);
                $existing = $stmt->fetchColumn();

                if ($existing) {
                    $db->prepare("UPDATE push_subscriptions SET p256dh=?,auth=?,updated_at=NOW() WHERE id=?")
                       ->execute([$p256dh, $auth, $existing]);
                } else {
                    $db->prepare("INSERT INTO push_subscriptions (user_id,type,endpoint,p256dh,auth) VALUES (?,?,?,?,?)")
                       ->execute([$userId,'web',$endpoint,$p256dh,$auth]);
                }

            } elseif ($type === 'fcm') {
                $token  = $input['token'] ?? '';
                $device = $input['device'] ?? '';
                if (!$token) { echo json_encode(['success' => false]); exit; }

                $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id=? AND type='fcm' AND fcm_token=?");
                $stmt->execute([$userId, $token]);
                $existing = $stmt->fetchColumn();

                if ($existing) {
                    $db->prepare("UPDATE push_subscriptions SET device_info=?,updated_at=NOW() WHERE id=?")
                       ->execute([$device, $existing]);
                } else {
                    $db->prepare("INSERT INTO push_subscriptions (user_id,type,fcm_token,device_info) VALUES (?,?,?,?)")
                       ->execute([$userId,'fcm',$token,$device]);
                }
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log('pushSubscribe error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
