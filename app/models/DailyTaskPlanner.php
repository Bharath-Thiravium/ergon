<?php
require_once __DIR__ . '/../../config/database.php';

class DailyTaskPlanner {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Get projects for user's department
    public function getProjectsByDepartment($department) {
        $query = "SELECT * FROM projects WHERE department = ? AND status = 'active' ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$department]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get task categories for department
    public function getTaskCategories($department) {
        $query = "SELECT * FROM task_categories WHERE department = ? AND is_active = 1 ORDER BY category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$department]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get tasks for a project and category
    public function getProjectTasks($projectId, $categoryId = null) {
        $query = "SELECT pt.*, tc.category_name FROM project_tasks pt 
                  JOIN task_categories tc ON pt.category_id = tc.id 
                  WHERE pt.project_id = ? AND pt.status = 'active'";
        $params = [$projectId];
        
        if ($categoryId) {
            $query .= " AND pt.category_id = ?";
            $params[] = $categoryId;
        }
        
        $query .= " ORDER BY tc.category_name, pt.task_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Submit daily task entry
    public function submitDailyTask($data) {
        $this->conn->beginTransaction();
        
        try {
            // Insert or update daily task entry
            $query = "INSERT INTO daily_task_entries 
                      (user_id, project_id, task_id, entry_date, progress_percentage, hours_spent, work_notes, attachment_path, gps_latitude, gps_longitude) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      progress_percentage = VALUES(progress_percentage),
                      hours_spent = VALUES(hours_spent),
                      work_notes = VALUES(work_notes),
                      attachment_path = VALUES(attachment_path),
                      gps_latitude = VALUES(gps_latitude),
                      gps_longitude = VALUES(gps_longitude)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['user_id'], $data['project_id'], $data['task_id'], $data['entry_date'],
                $data['progress_percentage'], $data['hours_spent'] ?? 0, $data['work_notes'],
                $data['attachment_path'] ?? null, $data['gps_latitude'] ?? null, $data['gps_longitude'] ?? null
            ]);
            
            // Update project task completion
            $this->updateTaskCompletion($data['task_id'], $data['progress_percentage']);
            
            // Update project completion percentage
            $this->updateProjectCompletion($data['project_id']);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // Get user's daily tasks for a date
    public function getUserDailyTasks($userId, $date) {
        $query = "SELECT dte.*, p.name as project_name, pt.task_name, tc.category_name
                  FROM daily_task_entries dte
                  JOIN projects p ON dte.project_id = p.id
                  JOIN project_tasks pt ON dte.task_id = pt.id
                  JOIN task_categories tc ON pt.category_id = tc.id
                  WHERE dte.user_id = ? AND dte.entry_date = ?
                  ORDER BY dte.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get project progress dashboard
    public function getProjectProgress() {
        $query = "SELECT p.*, 
                         COUNT(pt.id) as total_tasks,
                         COUNT(CASE WHEN pt.completion_percentage >= 100 THEN 1 END) as completed_tasks
                  FROM projects p
                  LEFT JOIN project_tasks pt ON p.id = pt.project_id
                  WHERE p.status = 'active'
                  GROUP BY p.id
                  ORDER BY p.completion_percentage DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get team daily activity
    public function getTeamDailyActivity($date, $department = null) {
        $query = "SELECT u.name, u.department, 
                         COUNT(dte.id) as tasks_updated,
                         AVG(dte.progress_percentage) as avg_progress,
                         SUM(dte.hours_spent) as total_hours
                  FROM users u
                  LEFT JOIN daily_task_entries dte ON u.id = dte.user_id AND dte.entry_date = ?
                  WHERE u.role = 'user' AND u.status = 'active'";
        $params = [$date];
        
        if ($department) {
            $query .= " AND u.department = ?";
            $params[] = $department;
        }
        
        $query .= " GROUP BY u.id ORDER BY tasks_updated DESC, avg_progress DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Private method to update task completion
    private function updateTaskCompletion($taskId, $progressPercentage) {
        $status = $progressPercentage >= 100 ? 'completed' : 'active';
        $query = "UPDATE project_tasks SET completion_percentage = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$progressPercentage, $status, $taskId]);
    }
    
    // Private method to update project completion
    private function updateProjectCompletion($projectId) {
        // Calculate weighted average of all tasks in the project
        $query = "SELECT 
                    SUM(completion_percentage * weight) / SUM(weight) as weighted_completion
                  FROM project_tasks 
                  WHERE project_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $completion = round($result['weighted_completion'] ?? 0, 2);
        $status = $completion >= 100 ? 'completed' : 'active';
        
        $query = "UPDATE projects SET completion_percentage = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$completion, $status, $projectId]);
    }
    
    // Get delayed tasks alert
    public function getDelayedTasks() {
        $query = "SELECT pt.*, p.name as project_name, tc.category_name,
                         DATEDIFF(CURDATE(), MAX(dte.entry_date)) as days_since_update
                  FROM project_tasks pt
                  JOIN projects p ON pt.project_id = p.id
                  JOIN task_categories tc ON pt.category_id = tc.id
                  LEFT JOIN daily_task_entries dte ON pt.id = dte.task_id
                  WHERE pt.status = 'active' AND pt.completion_percentage < 100
                  GROUP BY pt.id
                  HAVING days_since_update > 2 OR days_since_update IS NULL
                  ORDER BY days_since_update DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>