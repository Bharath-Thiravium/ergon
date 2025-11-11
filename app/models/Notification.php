<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureTable();
    }
    
    private function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            module_name VARCHAR(50) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_receiver_read (receiver_id, is_read),
            INDEX idx_created_at (created_at)
        )";
        $this->db->exec($sql);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['sender_id'],
            $data['receiver_id'],
            $data['module_name'],
            $data['action_type'],
            $data['message'],
            $data['reference_id'] ?? null
        ]);
    }
    
    public function getForUser($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT n.*, u.name as sender_name 
            FROM notifications n 
            JOIN users u ON n.sender_id = u.id 
            WHERE n.receiver_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    public function markAsRead($id, $userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        return $stmt->execute([$id, $userId]);
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE receiver_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public static function notify($senderId, $receiverId, $module, $action, $message, $referenceId = null) {
        $notification = new self();
        return $notification->create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'module_name' => $module,
            'action_type' => $action,
            'message' => $message,
            'reference_id' => $referenceId
        ]);
    }
    
    public static function notifyOwners($senderId, $module, $action, $message, $referenceId = null) {
        $notification = new self();
        $stmt = $notification->db->prepare("SELECT id FROM users WHERE role = 'owner'");
        $stmt->execute();
        $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($owners as $owner) {
            $notification->create([
                'sender_id' => $senderId,
                'receiver_id' => $owner['id'],
                'module_name' => $module,
                'action_type' => $action,
                'message' => $message,
                'reference_id' => $referenceId
            ]);
        }
    }
    
    public static function notifyAdmins($senderId, $module, $action, $message, $referenceId = null) {
        $notification = new self();
        $stmt = $notification->db->prepare("SELECT id FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $notification->create([
                'sender_id' => $senderId,
                'receiver_id' => $admin['id'],
                'module_name' => $module,
                'action_type' => $action,
                'message' => $message,
                'reference_id' => $referenceId
            ]);
        }
    }
}
?>