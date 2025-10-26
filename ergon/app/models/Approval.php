<?php
require_once __DIR__ . '/../config/database.php';

class Approval {
    private $db;
    private $table = 'approvals';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (type, reference_id, requested_by, approver_id, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['type'],
            $data['reference_id'],
            $data['requested_by'],
            $data['approver_id']
        ]);
    }
    
    public function updateStatus($id, $status, $approverId, $remarks = null) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = ?, approver_id = ?, remarks = ?, approved_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $approverId, $remarks, $id]);
    }
    
    public function getPendingApprovals($approverId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as requested_by_name 
            FROM {$this->table} a 
            JOIN users u ON a.requested_by = u.id 
            WHERE a.approver_id = ? AND a.status = 'pending' 
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$approverId]);
        return $stmt->fetchAll();
    }
}
?>