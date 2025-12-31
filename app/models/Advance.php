<?php

class Advance {
    private $db;
    private $table = 'advances';
    
    public function __construct() {
        $this->db = null;
        try {
            require_once __DIR__ . '/../config/database.php';
            $this->db = Database::connect();
            $this->ensureTableExists();
        } catch (Exception $e) {
            error_log('Advance model init error: ' . $e->getMessage());
        }
    }
    
    public function getAll() {
        if (!$this->db) {
            error_log('Advance getAll: No database connection');
            return [];
        }
        
        try {
            // Ensure table exists first
            $this->ensureTableExists();
            
            $stmt = $this->db->query("
                SELECT a.*, u.name as user_name 
                FROM {$this->table} a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Advance getAll error: ' . $e->getMessage());
            return [];
        }
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
            INSERT INTO {$this->table} (user_id, amount, reason, requested_date, repayment_date, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['amount'],
            $data['reason'],
            $data['requested_date'],
            $data['repayment_date'] ?? null,
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
    
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    reason TEXT NOT NULL,
                    requested_date DATE NULL,
                    repayment_date DATE NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    approved_by INT NULL,
                    approved_at DATETIME NULL,
                    admin_remarks TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                DatabaseHelper::safeExec($this->db, $sql, "Model operation");
                
            // Add repayment_date column if it doesn't exist in existing table
            try {
                DatabaseHelper::safeExec($this->db, "ALTER TABLE {$this->table} ADD COLUMN repayment_date DATE NULL", "Model operation");
            } catch (Exception $e) {
                // Column already exists, ignore error
            }
            }
        } catch (Exception $e) {
            error_log('Table creation error: ' . $e->getMessage());
        }
    }
}
?>
