<?php
require_once __DIR__ . '/../../config/database.php';

class Advance {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create($data) {
        $query = "INSERT INTO advances (user_id, type, amount, reason, repayment_date) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['amount'],
            $data['reason'],
            $data['repayment_date']
        ]);
    }
    
    public function getUserAdvances($userId) {
        $query = "SELECT * FROM advances WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingCount($userId) {
        $query = "SELECT COUNT(*) as count FROM advances WHERE user_id = ? AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}
?>