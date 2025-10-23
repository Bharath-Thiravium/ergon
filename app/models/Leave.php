<?php
require_once __DIR__ . '/../../config/database.php';

class Leave {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO leaves (employee_id, type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason']
        ]);
    }
    
    public function getAll() {
        $sql = "SELECT l.*, u.name as employee_name, a.name as approved_by_name 
                FROM leaves l 
                JOIN users u ON l.employee_id = u.id 
                LEFT JOIN users a ON l.approved_by = a.id 
                ORDER BY l.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByUserId($user_id) {
        $sql = "SELECT l.*, a.name as approved_by_name 
                FROM leaves l 
                LEFT JOIN users a ON l.approved_by = a.id 
                WHERE l.employee_id = ? 
                ORDER BY l.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($id, $status, $approved_by) {
        $sql = "UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $approved_by, $id]);
    }
    
    public function getStats($user_id = null) {
        $where = $user_id ? "WHERE employee_id = $user_id" : "";
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM leaves $where";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}