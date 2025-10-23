<?php
require_once __DIR__ . '/../../config/database.php';

class Expense {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO expenses (user_id, category, amount, description, receipt_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['category'],
            $data['amount'],
            $data['description'],
            $data['attachment'] ?? null
        ]);
    }
    
    public function getAll() {
        $sql = "SELECT e.*, u.name as user_name, a.name as approved_by_name 
                FROM expenses e 
                JOIN users u ON e.user_id = u.id 
                LEFT JOIN users a ON e.approved_by = a.id 
                ORDER BY e.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByUserId($user_id) {
        $sql = "SELECT e.*, a.name as approved_by_name 
                FROM expenses e 
                LEFT JOIN users a ON e.approved_by = a.id 
                WHERE e.user_id = ? 
                ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($id, $status, $approved_by) {
        $sql = "UPDATE expenses SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $approved_by, $id]);
    }
    
    public function getStats($user_id = null) {
        $where = $user_id ? "WHERE user_id = $user_id" : "";
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM expenses $where";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}