<?php
require_once __DIR__ . '/../../config/database.php';

class Task {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create($data) {
        $query = "INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['description'], $data['assigned_by'],
            $data['assigned_to'], $data['task_type'], $data['priority'], $data['deadline']
        ]);
    }
    
    public function getUserTasks($userId) {
        $query = "SELECT t.*, u.name as assigned_by_name FROM tasks t 
                  JOIN users u ON t.assigned_by = u.id 
                  WHERE t.assigned_to = ? ORDER BY t.deadline ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function updateProgress($taskId, $userId, $progress, $comment = null, $attachment = null) {
        $this->conn->beginTransaction();
        
        try {
            // Update task progress
            $query = "UPDATE tasks SET progress = ?, status = ? WHERE id = ?";
            $status = $progress >= 100 ? 'completed' : 'in_progress';
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$progress, $status, $taskId]);
            
            // Add update record
            $query = "INSERT INTO task_updates (task_id, user_id, progress, comment, attachment) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$taskId, $userId, $progress, $comment, $attachment]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    public function getTaskUpdates($taskId) {
        $query = "SELECT tu.*, u.name FROM task_updates tu 
                  JOIN users u ON tu.user_id = u.id 
                  WHERE tu.task_id = ? ORDER BY tu.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }
    
    public function getAllTasks() {
        $query = "SELECT t.*, 
                         u1.name as assigned_to_name,
                         u2.name as assigned_by_name
                  FROM tasks t 
                  JOIN users u1 ON t.assigned_to = u1.id 
                  JOIN users u2 ON t.assigned_by = u2.id 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTaskById($taskId) {
        $query = "SELECT t.*, 
                         u1.name as assigned_to_name,
                         u2.name as assigned_by_name
                  FROM tasks t 
                  JOIN users u1 ON t.assigned_to = u1.id 
                  JOIN users u2 ON t.assigned_by = u2.id 
                  WHERE t.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetch();
    }
    
    public function getTaskStats() {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN deadline < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
                  FROM tasks";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getTasksForCalendar() {
        $query = "SELECT t.id, t.title, t.deadline, t.status, t.priority, t.progress,
                         u.name as assigned_to_name
                  FROM tasks t 
                  JOIN users u ON t.assigned_to = u.id 
                  WHERE t.deadline >= CURDATE()
                  ORDER BY t.deadline ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>