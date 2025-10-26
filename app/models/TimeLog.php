<?php
require_once __DIR__ . '/../config/database.php';

class TimeLog {
    private $db;
    private $table = 'time_logs';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, task_id, start_time, end_time, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['task_id'],
            $data['start_time'],
            $data['end_time'],
            $data['description']
        ]);
    }
    
    public function getByUserId($userId, $date = null) {
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];
        
        if ($date) {
            $whereClause .= " AND DATE(start_time) = ?";
            $params[] = $date;
        }
        
        $stmt = $this->db->prepare("
            SELECT tl.*, t.title as task_title 
            FROM {$this->table} tl 
            LEFT JOIN tasks t ON tl.task_id = t.id 
            {$whereClause} 
            ORDER BY tl.start_time DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getTotalHours($userId, $startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60 as total_hours 
            FROM {$this->table} 
            WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $result = $stmt->fetch();
        return $result['total_hours'] ?? 0;
    }
}
?>