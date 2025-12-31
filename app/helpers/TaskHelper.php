<?php
/**
 * TaskHelper - Helper class for task and follow-up integration
 */

class TaskHelper {
    
    /**
     * Create a follow-up task linked to a contact
     */
    public static function createFollowupTask($data) {
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("
                INSERT INTO tasks (
                    title, description, assigned_by, assigned_to, 
                    type, priority, deadline, status, created_at
                ) VALUES (?, ?, ?, ?, 'followup', ?, ?, 'assigned', NOW())
            ");
            
            $result = $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['assigned_by'],
                $data['assigned_to'],
                $data['priority'] ?? 'medium',
                $data['deadline']
            ]);
            
            if ($result) {
                return $db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log('TaskHelper::createFollowupTask error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all follow-up tasks
     */
    public static function getFollowupTasks($userId = null) {
        try {
            $db = Database::connect();
            
            $sql = "
                SELECT t.*, 
                       u1.name as assigned_to_name,
                       u2.name as assigned_by_name,
                       f.contact_id,
                       c.name as contact_name,
                       c.phone as contact_phone
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                LEFT JOIN followups f ON t.id = f.task_id
                LEFT JOIN contacts c ON f.contact_id = c.id
                WHERE t.type = 'followup'
            ";
            
            if ($userId) {
                $sql .= " AND t.assigned_to = ?";
                $stmt = $db->prepare($sql . " ORDER BY t.deadline ASC");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY t.deadline ASC");
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('TaskHelper::getFollowupTasks error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync task status with follow-up status
     */
    public static function syncTaskFollowupStatus($taskId, $status) {
        try {
            $db = Database::connect();
            $db->beginTransaction();
            
            // Update task
            $progress = $status === 'completed' ? 100 : ($status === 'in_progress' ? 50 : 0);
            $stmt = $db->prepare("UPDATE tasks SET status = ?, progress = ? WHERE id = ?");
            $stmt->execute([$status, $progress, $taskId]);
            
            // Update linked follow-up
            $stmt = $db->prepare("UPDATE followups SET status = ? WHERE task_id = ?");
            $stmt->execute([$status, $taskId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log('TaskHelper::syncTaskFollowupStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Link existing task to a follow-up
     */
    public static function linkTaskToFollowup($taskId, $followupId) {
        try {
            $db = Database::connect();
            
            // Update task type to followup
            $stmt = $db->prepare("UPDATE tasks SET type = 'followup' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Link followup to task
            $stmt = $db->prepare("UPDATE followups SET task_id = ? WHERE id = ?");
            $result = $stmt->execute([$taskId, $followupId]);
            
            return $result;
        } catch (Exception $e) {
            error_log('TaskHelper::linkTaskToFollowup error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get task statistics for follow-ups
     */
    public static function getFollowupTaskStats($userId = null) {
        try {
            $db = Database::connect();
            
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue
                FROM tasks 
                WHERE type = 'followup'
            ";
            
            if ($userId) {
                $sql .= " AND assigned_to = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->prepare($sql);
                $stmt->execute();
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('TaskHelper::getFollowupTaskStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'overdue' => 0
            ];
        }
    }
    
    /**
     * Create follow-up from existing task
     */
    public static function createFollowupFromTask($taskId, $contactId, $followupDate) {
        try {
            $db = Database::connect();
            
            // Get task details
            $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                return false;
            }
            
            $db->beginTransaction();
            
            // Update task type
            $stmt = $db->prepare("UPDATE tasks SET type = 'followup' WHERE id = ?");
            $stmt->execute([$taskId]);
            
            // Create followup record
            $stmt = $db->prepare("
                INSERT INTO followups (
                    user_id, task_id, contact_id, title, description, 
                    follow_up_date, original_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $task['assigned_to'],
                $taskId,
                $contactId,
                $task['title'],
                $task['description'],
                $followupDate,
                $followupDate
            ]);
            
            if ($result) {
                $db->commit();
                return $db->lastInsertId();
            } else {
                $db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log('TaskHelper::createFollowupFromTask error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
