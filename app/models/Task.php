<?php
require_once __DIR__ . '/../config/database.php';

class Task {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    public function create($data) {
        $query = "INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, depends_on_task_id, sla_hours, parent_task_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['description'], $data['assigned_by'],
            $data['assigned_to'], $data['task_type'] ?? 'task', $data['priority'], $data['deadline'],
            $data['depends_on_task_id'] ?? null, $data['sla_hours'] ?? 24, $data['parent_task_id'] ?? null
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
    
    public function getAll() {
        $query = "SELECT t.*, 
                         u1.name as assigned_to_name,
                         u2.name as assigned_by_name
                  FROM tasks t 
                  LEFT JOIN users u1 ON t.assigned_to = u1.id 
                  LEFT JOIN users u2 ON t.assigned_by = u2.id 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByUserId($userId) {
        $query = "SELECT t.*, u.name as created_by_name 
                  FROM tasks t 
                  LEFT JOIN users u ON t.created_by = u.id 
                  WHERE t.assigned_to = ? 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function updateProgress($taskId, $userId, $progress, $comment = null, $attachment = null) {
        $this->conn->beginTransaction();
        
        try {
            $query = "UPDATE tasks SET progress = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'assigned');
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$progress, $status, $taskId]);
            
            $query = "INSERT INTO task_updates (task_id, user_id, progress, comment, attachments) 
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
    
    public function getTaskById($taskId) {
        $query = "SELECT t.*, 
                         u1.name as assigned_to_name,
                         u2.name as assigned_by_name
                  FROM tasks t 
                  LEFT JOIN users u1 ON t.assigned_to = u1.id 
                  LEFT JOIN users u2 ON t.assigned_by = u2.id 
                  WHERE t.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getTaskStats() {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as pending_tasks
                  FROM tasks";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, status = ?, priority = ?, progress = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['progress'] ?? 0,
            $id
        ]);
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
?>