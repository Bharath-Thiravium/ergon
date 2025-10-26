<?php
require_once __DIR__ . '/../config/database.php';

class Circular {
    private $db;
    private $table = 'circulars';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT c.*, u.name as created_by_name 
            FROM {$this->table} c 
            JOIN users u ON c.created_by = u.id 
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (title, content, created_by, target_audience, priority) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['content'],
            $data['created_by'],
            $data['target_audience'] ?? 'all',
            $data['priority'] ?? 'normal'
        ]);
    }
    
    public function getByAudience($audience) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE target_audience = ? OR target_audience = 'all' 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$audience]);
        return $stmt->fetchAll();
    }
}
?>
