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
        $query = "SELECT t.*, u.name as assigned_by_name 
                  FROM tasks t 
                  LEFT JOIN users u ON t.assigned_by = u.id 
                  WHERE t.assigned_to = ? 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function updateProgress($taskId, $userId, $progress, $description = null) {
        try {
            // Get current progress and status for history
            $query = "SELECT progress, status FROM tasks WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$taskId]);
            $current = $stmt->fetch();
            
            if (!$current) {
                throw new Exception('Task not found');
            }
            
            $oldProgress = $current['progress'];
            $oldStatus = $current['status'];
            $newStatus = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'assigned');
            
            // Update task progress and description
            $query = "UPDATE tasks SET progress = ?, status = ?, progress_description = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$progress, $newStatus, $description, $taskId]);
            
            // Insert progress history record
            $query = "INSERT INTO task_progress_history (task_id, user_id, progress_from, progress_to, description, status_from, status_to, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$taskId, $userId, $oldProgress, $progress, $description, $oldStatus, $newStatus]);
            
            // Sync with Daily Planner
            $this->syncWithDailyPlanner($taskId, $newStatus, $progress);
            
            return true;
        } catch (Exception $e) {
            error_log('Progress update error: ' . $e->getMessage());
            return false;
        }
    }
    

    
    public function getProgressHistory($taskId) {
        $query = "SELECT h.*, u.name as user_name 
                  FROM task_progress_history h 
                  LEFT JOIN users u ON h.user_id = u.id 
                  WHERE h.task_id = ? 
                  ORDER BY h.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
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
        $result = $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['progress'] ?? 0,
            $id
        ]);
        
        if ($result) {
            // Sync with Daily Planner
            $this->syncWithDailyPlanner($id, $data['status'], $data['progress'] ?? 0);
        }
        
        return $result;
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $id]);
        
        if ($result) {
            // Get current progress for sync
            $progressStmt = $this->conn->prepare("SELECT progress FROM tasks WHERE id = ?");
            $progressStmt->execute([$id]);
            $progress = $progressStmt->fetchColumn() ?: 0;
            
            // Sync with Daily Planner
            $this->syncWithDailyPlanner($id, $status, $progress);
        }
        
        return $result;
    }
    
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Delete from followups table first (cascade delete for linked followups)
            $stmt = $this->conn->prepare("DELETE FROM followups WHERE task_id = ?");
            $stmt->execute([$id]);
            
            // Delete from daily_tasks (cascade delete for planner entries)
            $stmt = $this->conn->prepare("DELETE FROM daily_tasks WHERE task_id = ? OR original_task_id = ?");
            $stmt->execute([$id, $id]);
            
            // Delete from tasks table (main task record)
            $stmt = $this->conn->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }
            error_log('Task delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByDepartment($departmentId) {
        $query = "SELECT t.*, 
                         u1.name as assigned_to_name,
                         u2.name as assigned_by_name
                  FROM tasks t 
                  LEFT JOIN users u1 ON t.assigned_to = u1.id 
                  LEFT JOIN users u2 ON t.assigned_by = u2.id 
                  WHERE u1.department = ? OR u2.department = ?
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$departmentId, $departmentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Sync task status and progress changes with Daily Planner
     * Enhanced to support follow-up completion scenarios
     */
    private function syncWithDailyPlanner($taskId, $status, $progress) {
        try {
            // Check if there are any daily_tasks entries for this task
            $checkQuery = "
                SELECT COUNT(*) as count, 
                       GROUP_CONCAT(DISTINCT scheduled_date) as dates,
                       GROUP_CONCAT(DISTINCT id) as daily_task_ids
                FROM daily_tasks 
                WHERE original_task_id = ? OR task_id = ?
            ";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$taskId, $taskId]);
            $plannerInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plannerInfo['count'] > 0) {
                // Update all daily_tasks entries that reference this task
                $query = "
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, 
                        completion_time = CASE WHEN ? = 'completed' THEN NOW() ELSE completion_time END,
                        updated_at = NOW()
                    WHERE original_task_id = ? OR task_id = ?
                ";
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$status, $progress, $status, $taskId, $taskId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    error_log("Task sync: Successfully updated {$stmt->rowCount()} Daily Planner entries for task {$taskId} on dates: {$plannerInfo['dates']}");
                    
                    // If task is completed, also sync with follow-ups
                    if ($status === 'completed') {
                        $this->syncWithFollowups($taskId, $status);
                    }
                } else {
                    error_log("Task sync: No Daily Planner entries were updated for task {$taskId}");
                }
            } else {
                error_log("Task sync: No Daily Planner entries found for task {$taskId} - task may not be scheduled in planner");
            }
        } catch (Exception $e) {
            error_log('Sync with Daily Planner error: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync task completion with linked follow-ups
     */
    private function syncWithFollowups($taskId, $status) {
        try {
            // Check if ContactFollowupController class exists and call its static method
            if (class_exists('ContactFollowupController')) {
                ContactFollowupController::updateLinkedFollowupStatus($taskId, $status);
            }
        } catch (Exception $e) {
            error_log('Sync with follow-ups error: ' . $e->getMessage());
        }
    }
}
?>
