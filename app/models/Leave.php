<?php
require_once __DIR__ . '/../../config/database.php';

class Leave {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($data) {
        try {
            $sql = "INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason) VALUES (?, ?, ?, ?, DATEDIFF(?, ?), ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['start_date'],
                $data['end_date'],
                $data['end_date'],
                $data['start_date'],
                $data['reason']
            ]);
        } catch (Exception $e) {
            error_log('Leave create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $sql = "SELECT l.*, u.name as employee_name 
                    FROM leaves l 
                    JOIN users u ON l.user_id = u.id 
                    ORDER BY l.created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByUserId($user_id) {
        try {
            $sql = "SELECT l.* FROM leaves l WHERE l.user_id = ? ORDER BY l.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getByUserId error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($id, $status, $approved_by) {
        $sql = "UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $approved_by, $id]);
    }
    
    public function getStats($user_id = null) {
        try {
            $where = $user_id ? "WHERE user_id = $user_id" : "";
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                    FROM leaves $where";
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }
}