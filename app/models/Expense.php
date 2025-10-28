<?php
require_once __DIR__ . '/../config/database.php';

class Expense {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        try {
            if (empty($data['user_id']) || empty($data['category']) || empty($data['amount'])) {
                return false;
            }
            
            $sql = "INSERT INTO expenses (user_id, category, amount, description, expense_date, attachment, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['user_id'],
                $data['category'],
                $data['amount'],
                $data['description'] ?? '',
                $data['expense_date'] ?? date('Y-m-d'),
                $data['attachment'] ?? null
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
            $sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getByUserId error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT e.*, u.name as user_name 
                    FROM expenses e 
                    JOIN users u ON e.user_id = u.id 
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function updateStatus($id, $status, $approved_by) {
        try {
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $sql = "UPDATE expenses SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $approved_by, $id]);
        } catch (Exception $e) {
            error_log('Expense updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getStats($user_id = null) {
        try {
            if ($user_id) {
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM expenses WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user_id]);
            } else {
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM expenses";
                $stmt = $this->db->query($sql);
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Expense getStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved_amount' => 0, 'rejected' => 0];
        }
    }
    
    public function delete($id) {
        try {
            $sql = "DELETE FROM expenses WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Expense delete error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
