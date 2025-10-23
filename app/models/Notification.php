<?php
require_once __DIR__ . '/../../config/database.php';

class Notification {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getUserNotifications($userId, $limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? OR user_id IS NULL 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read) 
                VALUES (?, ?, ?, ?, 0)
            ");
            return $stmt->execute([
                $data['user_id'],
                $data['title'],
                $data['message'],
                $data['type'] ?? 'info'
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>