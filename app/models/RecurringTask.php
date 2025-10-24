<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Task.php';

class RecurringTask {
    private $conn;
    private $taskModel;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->taskModel = new Task();
    }
    
    public function create($data) {
        $query = "INSERT INTO recurring_tasks 
                  (title, description, assigned_to, assigned_by, priority, 
                   recurrence_type, recurrence_interval, start_date, end_date, 
                   next_due_date, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['description'], $data['assigned_to'],
            $data['assigned_by'], $data['priority'], $data['recurrence_type'],
            $data['recurrence_interval'], $data['start_date'], $data['end_date'],
            $this->calculateNextDueDate($data['start_date'], $data['recurrence_type'], $data['recurrence_interval'])
        ]);
    }
    
    public function generateDueTasks() {
        $query = "SELECT * FROM recurring_tasks 
                  WHERE is_active = 1 
                    AND next_due_date <= CURDATE()
                    AND (end_date IS NULL OR end_date >= CURDATE())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $recurringTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $generated = 0;
        foreach ($recurringTasks as $recurring) {
            // Create new task instance
            $taskData = [
                'title' => $recurring['title'] . ' (' . date('M j, Y') . ')',
                'description' => $recurring['description'],
                'assigned_to' => $recurring['assigned_to'],
                'assigned_by' => $recurring['assigned_by'],
                'priority' => $recurring['priority'],
                'deadline' => $recurring['next_due_date'],
                'task_type' => 'recurring',
                'recurring_task_id' => $recurring['id']
            ];
            
            if ($this->taskModel->create($taskData)) {
                // Update next due date
                $nextDue = $this->calculateNextDueDate(
                    $recurring['next_due_date'],
                    $recurring['recurrence_type'],
                    $recurring['recurrence_interval']
                );
                
                $updateQuery = "UPDATE recurring_tasks SET next_due_date = ? WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->execute([$nextDue, $recurring['id']]);
                
                $generated++;
            }
        }
        
        return $generated;
    }
    
    private function calculateNextDueDate($currentDate, $type, $interval) {
        $date = new DateTime($currentDate);
        
        switch ($type) {
            case 'daily':
                $date->add(new DateInterval("P{$interval}D"));
                break;
            case 'weekly':
                $date->add(new DateInterval("P" . ($interval * 7) . "D"));
                break;
            case 'monthly':
                $date->add(new DateInterval("P{$interval}M"));
                break;
            case 'yearly':
                $date->add(new DateInterval("P{$interval}Y"));
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    public function getAll() {
        $query = "SELECT rt.*, u1.name as assigned_to_name, u2.name as assigned_by_name
                  FROM recurring_tasks rt
                  JOIN users u1 ON rt.assigned_to = u1.id
                  JOIN users u2 ON rt.assigned_by = u2.id
                  ORDER BY rt.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStreakData($userId, $recurringTaskId) {
        $query = "SELECT COUNT(*) as completed_count,
                         MAX(updated_at) as last_completion
                  FROM tasks 
                  WHERE assigned_to = ? 
                    AND recurring_task_id = ?
                    AND status = 'completed'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $recurringTaskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>