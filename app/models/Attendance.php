<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/TimezoneHelper.php';

class Attendance {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    private function setUserTimezone($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userPrefs = $stmt->fetch();
            $timezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
            date_default_timezone_set($timezone);
            return $timezone;
        } catch (Exception $e) {
            date_default_timezone_set('Asia/Kolkata');
            return 'Asia/Kolkata';
        }
    }
    
    public function checkIn($userId, $latitude, $longitude, $locationName, $clientUuid = null, $distance = 0, $isValid = true) {
        try {
            $existing = $this->getTodayAttendance($userId);
            if ($existing && !$existing['check_out']) {
                return false;
            }
            
            if ($clientUuid) {
                $stmt = $this->conn->prepare("SELECT id FROM attendance WHERE client_uuid = ?");
                $stmt->execute([$clientUuid]);
                if ($stmt->fetch()) {
                    return false;
                }
            }
            
            $currentTime = TimezoneHelper::getCurrentTime();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $query = "INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name, status, client_uuid, distance_meters, is_valid, ip_address) 
                      VALUES (?, ?, ?, ?, ?, 'present', ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$userId, $currentTime, $latitude, $longitude, $locationName, $clientUuid, $distance, $isValid ? 1 : 0, $ipAddress]);
            
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
            $currentTime = TimezoneHelper::getCurrentTime();
            $currentDate = TimezoneHelper::getCurrentDate();
            
            $query = "UPDATE attendance SET check_out = ? 
                      WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$currentTime, $userId, $currentDate]);
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('CheckOut error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTodayAttendance($userId) {
        $currentDate = TimezoneHelper::getCurrentDate();
        
        $query = "SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $currentDate]);
        return $stmt->fetch();
    }
    
    public function getAll() {
        try {
            $query = "SELECT a.*, u.name as user_name 
                      FROM attendance a 
                      JOIN users u ON a.user_id = u.id 
                      ORDER BY a.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Attendance getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getUserAttendance($userId) {
        try {
            $query = "SELECT a.*, u.name as user_name FROM attendance a 
                      JOIN users u ON a.user_id = u.id 
                      WHERE a.user_id = ? 
                      ORDER BY a.created_at DESC LIMIT 30";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Attendance getUserAttendance error: ' . $e->getMessage());
            return [];
        }
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
}
?>
