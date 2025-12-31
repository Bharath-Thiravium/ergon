<?php
/**
 * Modular Sync Service - Handles cross-module connections
 * Implements the Modular Connection Blueprint
 */

class ModularSyncService {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
    }
    
    /**
     * Sync task updates to daily planner
     */
    public function syncTaskToPlanner($taskId, $userId, $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        try {
            // Get task details
            $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) return false;
            
            // Check if already in planner for today
            $stmt = $this->db->prepare("SELECT id FROM daily_planner WHERE task_id = ? AND user_id = ? AND date = ?");
            $stmt->execute([$taskId, $userId, $date]);
            
            if ($stmt->rowCount() == 0) {
                // Add to planner
                $stmt = $this->db->prepare("
                    INSERT INTO daily_planner (user_id, task_id, date, title, description, status, priority_order) 
                    VALUES (?, ?, ?, ?, ?, 'planned', ?)
                ");
                $priorityOrder = $this->getNextPriorityOrder($userId, $date);
                $stmt->execute([$userId, $taskId, $date, $task['title'], $task['description'], $priorityOrder]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Sync task to planner error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync follow-up updates to tasks
     */
    public function syncFollowupToTask($followupId, $taskId = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM followups WHERE id = ?");
            $stmt->execute([$followupId]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) return false;
            
            // If no specific task ID, find related task by title
            if (!$taskId) {
                $stmt = $this->db->prepare("SELECT id FROM tasks WHERE title LIKE ? AND assigned_to = ?");
                $stmt->execute(['%' . $followup['title'] . '%', $followup['user_id']]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                $taskId = $task['id'] ?? null;
            }
            
            if ($taskId) {
                // Update task status based on follow-up status
                $taskStatus = $followup['status'] === 'completed' ? 'completed' : 'in_progress';
                $stmt = $this->db->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$taskStatus, $taskId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Sync followup to task error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Carry forward incomplete items to next day
     */
    public function executeCarryForward($userId, $targetDate) {
        try {
            $carriedItems = [];
            
            // Carry forward incomplete tasks
            $stmt = $this->db->prepare("
                SELECT * FROM tasks 
                WHERE assigned_to = ? 
                AND status IN ('assigned', 'in_progress') 
                AND progress < 100
            ");
            $stmt->execute([$userId]);
            $incompleteTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($incompleteTasks as $task) {
                $this->syncTaskToPlanner($task['id'], $userId, $targetDate);
                $carriedItems[] = ['type' => 'task', 'id' => $task['id'], 'title' => $task['title']];
            }
            
            // Carry forward pending follow-ups
            $stmt = $this->db->prepare("
                SELECT * FROM followups 
                WHERE user_id = ? 
                AND status IN ('pending', 'in_progress')
                AND follow_up_date < ?
            ");
            $stmt->execute([$userId, $targetDate]);
            $pendingFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pendingFollowups as $followup) {
                // Reschedule to target date
                $stmt = $this->db->prepare("UPDATE followups SET follow_up_date = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$targetDate, $followup['id']]);
                $carriedItems[] = ['type' => 'followup', 'id' => $followup['id'], 'title' => $followup['title']];
            }
            
            return $carriedItems;
        } catch (Exception $e) {
            error_log('Carry forward error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get today's follow-ups for planner integration
     */
    public function getTodaysFollowups($userId, $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        try {
            $stmt = $this->db->prepare("
                SELECT *, 'followup' as entry_type 
                FROM followups 
                WHERE user_id = ? AND follow_up_date = ? 
                ORDER BY reminder_time ASC
            ");
            $stmt->execute([$userId, $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Get todays followups error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getNextPriorityOrder($userId, $date) {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(priority_order), 0) + 1 FROM daily_planner WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchColumn();
    }
}
?>
