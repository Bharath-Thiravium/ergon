<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;
    private $table = 'notifications';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info'
        ]);
    }
    
    public function markAsRead($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}
?>
