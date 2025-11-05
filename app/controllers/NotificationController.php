<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class NotificationController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $title = 'Notifications';
        $active_page = 'notifications';
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureNotificationTable($db);
            
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            // Get notifications for the user
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no notifications exist, create some sample ones
            if (empty($notifications)) {
                $this->createSampleNotifications($db, $userId, $role);
                $stmt->execute([$userId]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch (Exception $e) {
            error_log('Notification index error: ' . $e->getMessage());
            $notifications = [];
        }
        
        ob_start();
        include __DIR__ . '/../../views/notifications/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/dashboard.php';
    }
    
    public function getUnreadCount() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureNotificationTable($db);
            
            $userId = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['count' => $result['count'] ?? 0]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['count' => 0]);
        }
        exit;
    }
    
    public function markAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
                    $stmt->execute([$id, $_SESSION['user_id']]);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } catch (Exception $e) {
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
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
                $stmt->execute([$_SESSION['user_id']]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    private function ensureNotificationTable($db) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($sql);
        } catch (Exception $e) {
            error_log('Notification table creation error: ' . $e->getMessage());
        }
    }
    
    private function createSampleNotifications($db, $userId, $role) {
        try {
            $notifications = [
                ['title' => 'Welcome to ERGON', 'message' => 'Welcome to the employee tracking system!', 'type' => 'success'],
                ['title' => 'New Task Available', 'message' => 'You have new tasks assigned to you', 'type' => 'info'],
                ['title' => 'System Update', 'message' => 'System has been updated with new features', 'type' => 'warning']
            ];
            
            if ($role === 'admin' || $role === 'owner') {
                $notifications[] = ['title' => 'Pending Approvals', 'message' => 'You have pending leave/expense requests to review', 'type' => 'warning'];
            }
            
            foreach ($notifications as $notification) {
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$userId, $notification['title'], $notification['message'], $notification['type']]);
            }
        } catch (Exception $e) {
            error_log('Sample notification creation error: ' . $e->getMessage());
        }
    }
}