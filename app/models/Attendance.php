<?php
require_once __DIR__ . '/../../config/database.php';

class Attendance {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function checkIn($userId, $latitude, $longitude, $locationName, $clientUuid = null, $distance = 0, $isValid = true) {
        try {
            // Check if already clocked in today
            $existing = $this->getTodayAttendance($userId);
            if ($existing && !$existing['check_out']) {
                return false; // Already clocked in
            }
            
            // Check for duplicate client_uuid
            if ($clientUuid) {
                $stmt = $this->conn->prepare("SELECT id FROM attendance WHERE client_uuid = ?");
                $stmt->execute([$clientUuid]);
                if ($stmt->fetch()) {
                    return false; // Duplicate request
                }
            }
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $query = "INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name, status, client_uuid, distance_meters, is_valid, ip_address) 
                      VALUES (?, NOW(), ?, ?, ?, 'present', ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$userId, $latitude, $longitude, $locationName, $clientUuid, $distance, $isValid ? 1 : 0, $ipAddress]);
            
            // Create conflict record if invalid
            if (!$isValid && $result) {
                $this->createConflict($userId, $this->conn->lastInsertId(), 'location_mismatch', "Distance: {$distance}m");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('CheckIn error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function checkOut($userId, $clientUuid = null) {
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
        $query = "SELECT a.*, u.name as user_name, 
                         CASE WHEN a.is_valid = 0 THEN 'Invalid Location' ELSE 'Valid' END as validation_status 
                  FROM attendance a 
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
                  ORDER BY a.created_at DESC LIMIT 30";
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
    
    public function createConflict($userId, $attendanceId, $type, $details) {
        $query = "INSERT INTO attendance_conflicts (user_id, attendance_id, conflict_type, details) 
                  VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$userId, $attendanceId, $type, $details]);
    }
    
    public function getConflicts($resolved = false) {
        $query = "SELECT ac.*, u.name as user_name, a.check_in, a.latitude, a.longitude 
                  FROM attendance_conflicts ac 
                  JOIN users u ON ac.user_id = u.id 
                  JOIN attendance a ON ac.attendance_id = a.id 
                  WHERE ac.resolved = ? 
                  ORDER BY ac.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$resolved ? 1 : 0]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function detectAnomalies($userId, $days = 30) {
        $anomalies = [];
        
        // Same day multiple check-ins from different locations
        $query = "SELECT DATE(check_in) as date, COUNT(*) as count, 
                         GROUP_CONCAT(CONCAT(latitude, ',', longitude)) as locations 
                  FROM attendance 
                  WHERE user_id = ? AND check_in >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                  GROUP BY DATE(check_in) 
                  HAVING count > 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $days]);
        $multipleCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($multipleCheckins as $record) {
            $locations = explode(',', $record['locations']);
            if (count(array_unique($locations)) > 1) {
                $anomalies[] = [
                    'type' => 'multiple_locations',
                    'date' => $record['date'],
                    'details' => "Multiple check-ins from different locations"
                ];
            }
        }
        
        // Repeated exact coordinates (possible spoofing)
        $query = "SELECT latitude, longitude, COUNT(*) as count 
                  FROM attendance 
                  WHERE user_id = ? AND check_in >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                  GROUP BY latitude, longitude 
                  HAVING count > 10";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $days]);
        $repeatedCoords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($repeatedCoords as $coord) {
            $anomalies[] = [
                'type' => 'repeated_coordinates',
                'details' => "Same coordinates used {$coord['count']} times",
                'location' => "{$coord['latitude']},{$coord['longitude']}"
            ];
        }
        
        return $anomalies;
    }
}
?>