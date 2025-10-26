<?php
require_once __DIR__ . '/../config/database.php';

class Advance {
    private $db;
    private $table = 'advances';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT a.*, u.name as user_name 
            FROM {$this->table} a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll();
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
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, amount, reason, requested_date, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['amount'],
            $data['reason'],
            $data['requested_date'],
            $data['status']
        ]);
    }
    
    public function updateStatus($id, $status, $remarks = null) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = ?, admin_remarks = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $remarks, $id]);
    }
}
?>