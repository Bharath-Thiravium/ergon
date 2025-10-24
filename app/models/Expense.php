<?php
require_once __DIR__ . '/../../config/database.php';

class Expense {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($data) {
        try {
            $sql = "INSERT INTO expenses (user_id, category, amount, description, receipt_path, expense_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['user_id'],
                $data['category'],
                $data['amount'],
                $data['description'],
                $data['attachment'] ?? null,
                $data['date'] ?? date('Y-m-d')
            ]);
        } catch (Exception $e) {
            error_log('Expense create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $sql = "SELECT e.*, u.name as user_name 
                    FROM expenses e 
                    JOIN users u ON e.user_id = u.id 
                    ORDER BY e.created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByUserId($user_id) {
        try {
            $sql = "SELECT e.* FROM expenses e WHERE e.user_id = ? ORDER BY e.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getByUserId error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($id, $status, $approved_by) {
        try {
            $sql = "UPDATE expenses SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            error_log('Expense updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getById error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getStats($user_id = null) {
        try {
            $where = $user_id ? "WHERE user_id = $user_id" : "";
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM expenses $where";
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved_amount' => 0, 'rejected' => 0];
        }
    }
}