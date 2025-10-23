<?php
require_once __DIR__ . '/../../config/database.php';

class Attendance {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function checkIn($userId, $latitude, $longitude, $locationName) {
        try {
            // Check if already clocked in today
            $existing = $this->getTodayAttendance($userId);
            if ($existing && !$existing['check_out']) {
                return false; // Already clocked in
            }
            
            $query = "INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name, status) 
                      VALUES (?, NOW(), ?, ?, ?, 'present')";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$userId, $latitude, $longitude, $locationName]);
        } catch (Exception $e) {
            error_log('CheckIn error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function checkOut($userId) {
        try {
            $query = "UPDATE attendance SET check_out = NOW() 
                      WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$userId]);
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('CheckOut error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTodayAttendance($userId) {
        $query = "SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getAll() {
        $query = "SELECT a.*, u.name as user_name FROM attendance a 
                  JOIN users u ON a.user_id = u.id 
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserAttendance($userId) {
        $query = "SELECT a.*, u.name as user_name FROM attendance a 
                  JOIN users u ON a.user_id = u.id 
                  WHERE a.user_id = ? 
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAttendanceReport($startDate, $endDate, $userId = null) {
        $query = "SELECT a.*, u.name FROM attendance a 
                  JOIN users u ON a.user_id = u.id 
                  WHERE DATE(a.check_in) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        if ($userId) {
            $query .= " AND a.user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>