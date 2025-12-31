<?php
require_once __DIR__ . '/../config/database.php';

class Expense {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::connect();
            $this->ensureExpenseTable();
        } catch (Exception $e) {
            error_log('Expense model: Database connection failed - ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function ensureExpenseTable() {
        try {
            // First check if table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'expenses'");
            if ($stmt->rowCount() == 0) {
                // Create new table
                $sql = "CREATE TABLE expenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category VARCHAR(100) NOT NULL DEFAULT 'general',
                    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    description TEXT,
                    expense_date DATE NOT NULL,
                    attachment VARCHAR(255) NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    approved_by INT NULL,
                    approved_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                DatabaseHelper::safeExec($this->db, $sql, "Model operation");
                error_log('Expenses table created successfully');
            } else {
                // Table exists, check if expense_date column exists
                $stmt = $this->db->query("SHOW COLUMNS FROM expenses LIKE 'expense_date'");
                if ($stmt->rowCount() == 0) {
                    DatabaseHelper::safeExec($this->db, "ALTER TABLE expenses ADD COLUMN expense_date DATE NOT NULL DEFAULT (CURDATE())", "Model operation");
                    error_log('Added expense_date column to existing expenses table');
                }
            }
        } catch (Exception $e) {
            error_log('Error ensuring expense table: ' . $e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['user_id']) || empty($data['category']) || empty($data['amount'])) {
                return false;
            }
            
            $sql = "INSERT INTO expenses (user_id, project_id, category, amount, description, expense_date, attachment, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = $this->db->prepare($sql);
            
            $params = [
                $data['user_id'],
                $data['project_id'] ?? null,
                $data['category'],
                $data['amount'],
                $data['description'] ?? '',
                $data['expense_date'] ?? date('Y-m-d'),
                $data['attachment'] ?? null
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log('Expense SQL Error: ' . implode(' - ', $errorInfo));
                error_log('SQL: ' . $sql);
                error_log('Params: ' . json_encode($params));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Expense create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $sql = "SELECT e.*, u.name as user_name, u.role as user_role 
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
            $sql = "SELECT e.*, u.name as user_name, u.role as user_role 
                    FROM expenses e 
                    JOIN users u ON e.user_id = u.id 
                    WHERE e.user_id = ? ORDER BY e.created_at DESC";
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
                // Total and pending/rejected counts come from expenses table.
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM expenses WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                // Approved amount should be taken from approved_expenses.approved_amount (keeps admin-approved separate from claimed amount)
                $stmt2 = $this->db->prepare("SELECT COALESCE(SUM(approved_amount),0) as approved_amount FROM approved_expenses WHERE user_id = ?");
                $stmt2->execute([$user_id]);
                $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                $row['approved_amount'] = $row2['approved_amount'] ?? 0;
                return $row;
            } else {
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM expenses";
                $stmt = $this->db->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt2 = $this->db->query("SELECT COALESCE(SUM(approved_amount),0) as approved_amount FROM approved_expenses");
                $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                $row['approved_amount'] = $row2['approved_amount'] ?? 0;
                return $row;
            }
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
