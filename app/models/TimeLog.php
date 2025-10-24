<?php
require_once __DIR__ . '/../../config/database.php';

class TimeLog {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function startTimer($taskId, $userId, $description = '') {
        // Stop any running timer first
        $this->stopActiveTimer($userId);
        
        $query = "INSERT INTO task_time_logs (task_id, user_id, start_time, description, status) 
                  VALUES (?, ?, NOW(), ?, 'active')";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$taskId, $userId, $description]);
    }
    
    public function stopTimer($logId, $userId) {
        $query = "UPDATE task_time_logs 
                  SET end_time = NOW(), 
                      duration = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                      status = 'completed'
                  WHERE id = ? AND user_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$logId, $userId]);
    }
    
    public function stopActiveTimer($userId) {
        $query = "UPDATE task_time_logs 
                  SET end_time = NOW(), 
                      duration = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                      status = 'completed'
                  WHERE user_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$userId]);
    }
    
    public function getActiveTimer($userId) {
        $query = "SELECT tl.*, t.title as task_title 
                  FROM task_time_logs tl
                  JOIN tasks t ON tl.task_id = t.id
                  WHERE tl.user_id = ? AND tl.status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getTaskTimeSpent($taskId) {
        $query = "SELECT SUM(duration) as total_seconds,
                         COUNT(*) as session_count,
                         AVG(duration) as avg_session
                  FROM task_time_logs 
                  WHERE task_id = ? AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUserTimesheet($userId, $startDate, $endDate) {
        $query = "SELECT tl.*, t.title as task_title,
                         DATE(tl.start_time) as work_date,
                         TIME(tl.start_time) as start_time_only,
                         TIME(tl.end_time) as end_time_only
                  FROM task_time_logs tl
                  JOIN tasks t ON tl.task_id = t.id
                  WHERE tl.user_id = ? 
                    AND DATE(tl.start_time) BETWEEN ? AND ?
                    AND tl.status = 'completed'
                  ORDER BY tl.start_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductivityMetrics($userId, $days = 30) {
        $query = "SELECT 
                    DATE(start_time) as date,
                    SUM(duration) as daily_seconds,
                    COUNT(DISTINCT task_id) as tasks_worked,
                    COUNT(*) as sessions
                  FROM task_time_logs 
                  WHERE user_id = ? 
                    AND start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND status = 'completed'
                  GROUP BY DATE(start_time)
                  ORDER BY date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>