<?php
require_once __DIR__ . '/../config/database.php';

class SyncService {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function syncTaskProgress($taskId, $progress, $status, $userId) {
        $this->db->beginTransaction();
        
        try {
            // Update task
            $stmt = $this->db->prepare("
                UPDATE tasks SET 
                    progress = ?, 
                    status = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$progress, $status, $taskId]);
            
            // Update related planner entries
            $stmt = $this->db->prepare("
                UPDATE daily_planner SET 
                    status = CASE 
                        WHEN ? >= 100 THEN 'completed'
                        WHEN ? > 0 THEN 'in_progress'
                        ELSE 'planned'
                    END
                WHERE task_id = ?
            ");
            $stmt->execute([$progress, $progress, $taskId]);
            

            
            // Auto-create follow-up if task completed and has follow-up category
            if ($progress >= 100) {
                $this->checkAutoFollowupCreation($taskId);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Sync error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function syncFollowupStatus($followupId, $status, $userId) {
        $this->db->beginTransaction();
        
        try {
            // Update followup
            $stmt = $this->db->prepare("
                UPDATE followups SET 
                    status = ?, 
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $status, $followupId]);
            
            // Create planner entry for today if rescheduled to today
            if ($status === 'pending') {
                $this->createPlannerFromFollowup($followupId, $userId);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Followup sync error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function executeSmartCarryForward($userId, $targetDate) {
        $this->db->beginTransaction();
        
        try {
            $carriedItems = [];
            
            // Carry forward incomplete tasks
            $incompleteTasks = $this->getIncompleteTasksForCarryForward($userId, $targetDate);
            foreach ($incompleteTasks as $task) {
                $this->carryForwardTask($task, $targetDate, $userId);
                $carriedItems[] = ['type' => 'task', 'id' => $task['id'], 'title' => $task['title']];
            }
            
            // Carry forward overdue follow-ups
            $overdueFollowups = $this->getOverdueFollowupsForCarryForward($userId, $targetDate);
            foreach ($overdueFollowups as $followup) {
                $this->carryForwardFollowup($followup, $targetDate);
                $carriedItems[] = ['type' => 'followup', 'id' => $followup['id'], 'title' => $followup['title']];
            }
            
            // Auto-escalate high priority overdue items
            $this->autoEscalateOverdueItems($userId);
            
            $this->db->commit();
            return $carriedItems;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Carry forward error: ' . $e->getMessage());
            throw $e;
        }
    }
    

    
    private function checkAutoFollowupCreation($taskId) {
        // Get task details
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task && stripos($task['task_category'], 'follow') !== false) {
            // Create follow-up for next business day
            $followupDate = $this->getNextBusinessDay();
            
            $stmt = $this->db->prepare("
                INSERT INTO followups (
                    user_id, title, description, follow_up_date, 
                    original_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $task['assigned_to'],
                'Follow-up: ' . $task['title'],
                'Auto-created follow-up for completed task: ' . $task['description'],
                $followupDate,
                $followupDate
            ]);
            
            error_log('Auto-followup created for completed task: ' . $taskId);
        }
    }
    
    private function createPlannerFromFollowup($followupId, $userId) {
        $stmt = $this->db->prepare("SELECT * FROM followups WHERE id = ?");
        $stmt->execute([$followupId]);
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($followup && $followup['follow_up_date'] === date('Y-m-d')) {
            // Check if planner entry already exists
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM daily_planner 
                WHERE user_id = ? AND date = ? AND title LIKE ?
            ");
            $stmt->execute([$userId, date('Y-m-d'), '%' . $followup['title'] . '%']);
            
            if ($stmt->fetchColumn() == 0) {
                // Create planner entry
                $stmt = $this->db->prepare("
                    INSERT INTO daily_planner (
                        user_id, date, title, description, priority_order, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'planned', NOW())
                ");
                
                $priorityOrder = $this->getNextPriorityOrder($userId, date('Y-m-d'));
                
                $stmt->execute([
                    $userId,
                    date('Y-m-d'),
                    '[FOLLOW-UP] ' . $followup['title'],
                    $followup['description'],
                    $priorityOrder
                ]);
            }
        }
    }
    
    private function getIncompleteTasksForCarryForward($userId, $targetDate) {
        $stmt = $this->db->prepare("
            SELECT * FROM tasks 
            WHERE assigned_to = ? 
            AND status IN ('assigned', 'in_progress', 'blocked')
            AND progress < 100
            AND (deadline < ? OR deadline IS NULL)
        ");
        $stmt->execute([$userId, $targetDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getOverdueFollowupsForCarryForward($userId, $targetDate) {
        $stmt = $this->db->prepare("
            SELECT * FROM followups 
            WHERE user_id = ? 
            AND status IN ('pending', 'in_progress')
            AND follow_up_date < ?
        ");
        $stmt->execute([$userId, $targetDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function carryForwardTask($task, $targetDate, $userId) {
        // Boost priority for overdue tasks
        $newPriority = $this->boostPriority($task['priority'], $task['deadline'], $targetDate);
        
        // Update task priority
        $stmt = $this->db->prepare("UPDATE tasks SET priority = ? WHERE id = ?");
        $stmt->execute([$newPriority, $task['id']]);
        
        // Create planner entry
        $stmt = $this->db->prepare("
            INSERT INTO daily_planner (
                user_id, task_id, date, title, description, 
                priority_order, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'carried_forward', NOW())
        ");
        
        $priorityOrder = $this->getNextPriorityOrder($userId, $targetDate);
        
        $stmt->execute([
            $userId, $task['id'], $targetDate,
            '[CARRIED] ' . $task['title'],
            $task['description'], $priorityOrder
        ]);
    }
    
    private function carryForwardFollowup($followup, $targetDate) {
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                follow_up_date = ?, 
                status = 'pending',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$targetDate, $followup['id']]);
    }
    
    private function autoEscalateOverdueItems($userId) {
        // Escalate tasks overdue by more than 2 days
        $stmt = $this->db->prepare("
            UPDATE tasks SET 
                priority = CASE 
                    WHEN priority = 'low' THEN 'medium'
                    WHEN priority = 'medium' THEN 'high'
                    ELSE priority
                END
            WHERE assigned_to = ? 
            AND deadline < DATE_SUB(CURDATE(), INTERVAL 2 DAY)
            AND status IN ('assigned', 'in_progress')
        ");
        $stmt->execute([$userId]);
        
        // Escalate follow-ups overdue by more than 3 days
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                status = 'in_progress'
            WHERE user_id = ? 
            AND follow_up_date < DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            AND status = 'pending'
        ");
        $stmt->execute([$userId]);
    }
    
    private function boostPriority($currentPriority, $deadline, $targetDate) {
        if ($deadline && $deadline < $targetDate) {
            return $currentPriority === 'low' ? 'medium' : 'high';
        }
        return $currentPriority;
    }
    
    private function getNextPriorityOrder($userId, $date) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(priority_order), 0) + 1 
            FROM daily_planner 
            WHERE user_id = ? AND date = ?
        ");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchColumn();
    }
    
    private function getNextBusinessDay() {
        $date = new DateTime();
        $date->add(new DateInterval('P1D'));
        
        // Skip weekends
        while ($date->format('N') >= 6) {
            $date->add(new DateInterval('P1D'));
        }
        
        return $date->format('Y-m-d');
    }
    

}
?>