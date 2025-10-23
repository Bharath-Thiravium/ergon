<?php
require_once __DIR__ . '/../../config/database.php';

class Task {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
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
    
    public function updateProgress($taskId, $userId, $progress, $comment = null, $attachment = null) {
        $this->conn->beginTransaction();
        
        try {
            // Update task progress
            $query = "UPDATE tasks SET progress = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'assigned');
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$progress, $status, $taskId]);
            
            // Add update record
            $query = "INSERT INTO task_updates (task_id, user_id, progress, comment, attachments) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$taskId, $userId, $progress, $comment, $attachment]);
            
            // Check and update parent task progress if this is a subtask
            $this->updateParentTaskProgress($taskId);
            
            // Check dependencies and notify if blocking tasks are resolved
            $this->checkDependencies($taskId);
            
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
    
    private function updateParentTaskProgress($taskId) {
        // Get parent task
        $query = "SELECT parent_task_id FROM tasks WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task || !$task['parent_task_id']) return;
        
        // Calculate average progress of all subtasks
        $query = "SELECT AVG(progress) as avg_progress FROM tasks WHERE parent_task_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$task['parent_task_id']]);
        $result = $stmt->fetch();
        
        $avgProgress = round($result['avg_progress']);
        $status = $avgProgress >= 100 ? 'completed' : ($avgProgress > 0 ? 'in_progress' : 'assigned');
        
        // Update parent task
        $query = "UPDATE tasks SET progress = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$avgProgress, $status, $task['parent_task_id']]);
    }
    
    private function checkDependencies($taskId) {
        // Find tasks that depend on this one
        $query = "SELECT id, assigned_to FROM tasks WHERE depends_on_task_id = ? AND status != 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        $dependentTasks = $stmt->fetchAll();
        
        // Check if this task is completed
        $query = "SELECT status FROM tasks WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        $currentTask = $stmt->fetch();
        
        if ($currentTask['status'] === 'completed') {
            // Notify users of dependent tasks
            require_once __DIR__ . '/../helpers/NotificationHelper.php';
            foreach ($dependentTasks as $depTask) {
                NotificationHelper::notifyUser(
                    $depTask['assigned_to'],
                    'Task Dependency Resolved',
                    'A task you depend on has been completed. You can now proceed.',
                    '/ergon/tasks'
                );
            }
        }
    }
    
    public function getOverdueTasks() {
        $query = "SELECT t.*, u.name as assigned_to_name, 
                         TIMESTAMPDIFF(HOUR, t.deadline, NOW()) as hours_overdue
                  FROM tasks t 
                  JOIN users u ON t.assigned_to = u.id 
                  WHERE t.deadline < NOW() AND t.status != 'completed'
                  ORDER BY t.deadline ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSLABreaches() {
        $query = "SELECT t.*, u.name as assigned_to_name,
                         TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_elapsed
                  FROM tasks t 
                  JOIN users u ON t.assigned_to = u.id 
                  WHERE t.status != 'completed' 
                    AND TIMESTAMPDIFF(HOUR, t.created_at, NOW()) > t.sla_hours
                  ORDER BY hours_elapsed DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTaskVelocity($userId, $days = 30) {
        $query = "SELECT DATE(tu.created_at) as date, 
                         AVG(tu.progress - COALESCE(prev.progress, 0)) as daily_progress
                  FROM task_updates tu
                  LEFT JOIN task_updates prev ON prev.task_id = tu.task_id 
                    AND prev.created_at < tu.created_at
                    AND prev.id = (SELECT MAX(id) FROM task_updates WHERE task_id = tu.task_id AND created_at < tu.created_at)
                  WHERE tu.user_id = ? AND tu.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(tu.created_at)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductivityScore($userId, $days = 30) {
        $query = "SELECT 
                    COUNT(DISTINCT t.id) as tasks_worked,
                    AVG(t.progress) as avg_progress,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 END) as missed_deadlines
                  FROM tasks t
                  JOIN task_updates tu ON t.id = tu.task_id
                  WHERE tu.user_id = ? AND tu.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $days]);
        $stats = $stmt->fetch();
        
        // Calculate productivity score (0-100)
        $score = 0;
        if ($stats['tasks_worked'] > 0) {
            $completionRate = ($stats['completed_tasks'] / $stats['tasks_worked']) * 100;
            $progressRate = $stats['avg_progress'];
            $penaltyRate = ($stats['missed_deadlines'] / $stats['tasks_worked']) * 20;
            
            $score = max(0, min(100, ($completionRate * 0.5) + ($progressRate * 0.4) - $penaltyRate));
        }
        
        return [
            'score' => round($score, 1),
            'stats' => $stats
        ];
    }
    
    public function getSubtasks($parentTaskId) {
        $query = "SELECT t.*, u.name as assigned_to_name 
                  FROM tasks t 
                  JOIN users u ON t.assigned_to = u.id 
                  WHERE t.parent_task_id = ? 
                  ORDER BY t.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$parentTaskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createBulkTasks($tasks) {
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, deadline, task_type) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($tasks as $task) {
                $stmt->execute([
                    $task['title'], $task['description'], $task['assigned_by'],
                    $task['assigned_to'], $task['priority'], $task['deadline'], 'task'
                ]);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>