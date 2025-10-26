<?php
require_once __DIR__ . '/../config/database.php';

class ActivityLog {
    private $db;
    private $table = 'activity_logs';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function log($userId, $action, $details = null) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
    
    public function getByUserId($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getAll($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.name as user_name 
            FROM {$this->table} al 
            JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>
