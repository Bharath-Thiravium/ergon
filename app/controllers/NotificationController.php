<?php

class NotificationController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $title = 'Notifications';
        $active_page = 'notifications';
        
        try {
            $notifications = [
                ['id' => 1, 'title' => 'New Task Assigned', 'message' => 'You have been assigned a new task', 'type' => 'info', 'read' => false, 'created_at' => date('Y-m-d H:i:s')],
                ['id' => 2, 'title' => 'Leave Approved', 'message' => 'Your leave request has been approved', 'type' => 'success', 'read' => false, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                ['id' => 3, 'title' => 'System Maintenance', 'message' => 'System will be under maintenance tonight', 'type' => 'warning', 'read' => true, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
            ];
            
            $data = ['notifications' => $notifications];
            
            ob_start();
            include __DIR__ . '/../../views/notifications/index.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to load notifications');
        }
    }
    
    public function getUnreadCount() {
        $this->requireAuth();
        
        try {
            // Mock unread count
            $count = 2;
            echo json_encode(['count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function markAsRead() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Mock mark as read
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}
