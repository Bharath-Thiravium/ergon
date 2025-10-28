<?php
require_once __DIR__ . '/../config/database.php';

class Leave {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['user_id']) || empty($data['type']) || empty($data['start_date']) || empty($data['end_date'])) {
                return false;
            }
            
            // Calculate days
            $start = new DateTime($data['start_date']);
            $end = new DateTime($data['end_date']);
            $days = $start->diff($end)->days + 1;
            
            $sql = "INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['start_date'],
                $data['end_date'],
                $days,
                $data['reason'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log('Leave create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $sql = "SELECT l.*, u.name as user_name 
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
            $sql = "SELECT * FROM leaves WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getByUserId error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT l.*, u.name as user_name 
                    FROM leaves l 
                    JOIN users u ON l.user_id = u.id 
                    WHERE l.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function updateStatus($id, $status, $approved_by) {
        try {
            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $sql = "UPDATE leaves SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $approved_by, $id]);
        } catch (Exception $e) {
            error_log('Leave updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getStats($user_id = null) {
        try {
            if ($user_id) {
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM leaves WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user_id]);
            } else {
                $sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM leaves";
                $stmt = $this->db->query($sql);
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Leave getStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }
    
    public function hasOverlappingLeave($user_id, $start_date, $end_date, $exclude_id = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM leaves 
                    WHERE user_id = ? 
                    AND status = 'approved'
                    AND (
                        (start_date <= ? AND end_date >= ?) OR
                        (start_date <= ? AND end_date >= ?) OR
                        (start_date >= ? AND end_date <= ?)
                    )";
            
            $params = [$user_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log('Leave hasOverlappingLeave error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $sql = "DELETE FROM leaves WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Leave delete error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
