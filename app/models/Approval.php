<?php
require_once __DIR__ . '/../../config/database.php';

class Approval {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($module, $record_id, $requested_by, $remarks = null) {
        $sql = "INSERT INTO approvals (module, record_id, requested_by, remarks) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$module, $record_id, $requested_by, $remarks]);
    }
    
    public function approve($id, $approved_by, $remarks = null) {
        $sql = "UPDATE approvals SET status = 'Approved', approved_by = ?, remarks = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$approved_by, $remarks, $id]);
    }
    
    public function reject($id, $approved_by, $remarks = null) {
        $sql = "UPDATE approvals SET status = 'Rejected', approved_by = ?, remarks = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$approved_by, $remarks, $id]);
    }
    
    public function getPending($module = null) {
        $where = $module ? "WHERE module = '$module' AND" : "WHERE";
        $sql = "SELECT a.*, u.name as requested_by_name 
                FROM approvals a 
                JOIN users u ON a.requested_by = u.id 
                $where status = 'Pending' 
                ORDER BY a.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}