<?php

/**
 * RolloverTaskManager - Implements the exact rollover specification
 * 
 * 🔁 Step 1: Detect Eligible Tasks for Rollover
 * 📦 Step 2: Perform Rollover to Today
 * 🖥️ Step 3: Display Tasks in UI
 */
class RolloverTaskManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureTasksTable();
    }
    
    /**
     * 🔁 Step 1: Detect Eligible Tasks for Rollover
     * Function: getRolloverTasks()
     * Trigger: Daily at midnight (via scheduler) OR on accessing today's planner view
     */
    public function getRolloverTasks($userId = null) {
        try {
            $today = date('Y-m-d');
            
            // Query tasks where:
            // - task_date < today
            // - status IN ('Pending', 'In Progress')  
            // - rolled_from_date IS NULL
            $whereClause = "task_date < ? AND status IN ('Pending', 'In Progress') AND rolled_from_date IS NULL";
            $params = [$today];
            
            // User-specific filtering if provided
            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            // Exclude tasks already rolled over or completed
            $whereClause .= " AND NOT EXISTS (
                SELECT 1 FROM tasks t2 
                WHERE t2.source_task_id = tasks.id 
                AND t2.rolled_from_date IS NOT NULL
            )";
            
            $stmt = $this->db->prepare("
                SELECT id, user_id, task_date as source_date, status, description, priority, sla_hours
                FROM tasks 
                WHERE {$whereClause}
                ORDER BY task_date ASC, priority DESC
            ");
            
            $stmt->execute($params);
            $eligibleTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Audit Trail: Log detection timestamp
            $this->logRolloverDetection(count($eligibleTasks), $userId);
            
            // Record each task in task_history
            foreach ($eligibleTasks as $task) {
                $this->logTaskHistory(
                    $task['id'], 
                    $task['user_id'], 
                    'rollover_detected', 
                    $task['source_date'], 
                    $today,
                    "Task detected for rollover from {$task['source_date']}"
                );
            }
            
            return $eligibleTasks;
            
        } catch (Exception $e) {
            error_log("getRolloverTasks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 📦 Step 2: Perform Rollover to Today
     * Function: performRollover()
     */
    public function performRollover($eligibleTasks = null) {
        try {
            if ($eligibleTasks === null) {
                $eligibleTasks = $this->getRolloverTasks();
            }
            
            $today = date('Y-m-d');
            $rolledOverCount = 0;
            
            $this->db->beginTransaction();
            
            foreach ($eligibleTasks as $task) {
                // Create a new task entry
                $stmt = $this->db->prepare("
                    INSERT INTO tasks (
                        user_id, task_date, description, status, priority, sla_hours,
                        created_at, rolled_from_date, source_task_id
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                ");
                
                $newStatus = $task['status']; // Preserve status unless overridden by config
                
                $result = $stmt->execute([
                    $task['user_id'],
                    $today,
                    $task['description'],
                    $newStatus,
                    $task['priority'],
                    $task['sla_hours'],
                    $task['source_date'],
                    $task['id']
                ]);
                
                if ($result) {
                    $newTaskId = $this->db->lastInsertId();
                    
                    // Update original task status to 'Rolled Over'
                    $updateStmt = $this->db->prepare("
                        UPDATE tasks 
                        SET status = 'Rolled Over', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$task['id']]);
                    
                    // Audit Trail: Log rollover action
                    $this->logTaskHistory(
                        $newTaskId,
                        $task['user_id'],
                        'rollover',
                        $task['id'],
                        $newTaskId,
                        "🔄 Rolled over from: {$task['source_date']}"
                    );
                    
                    $this->logTaskHistory(
                        $task['id'],
                        $task['user_id'],
                        'rolled_over',
                        $task['status'],
                        'Rolled Over',
                        "Original task rolled over to {$today}"
                    );
                    
                    $rolledOverCount++;
                }
            }
            
            $this->db->commit();
            
            // Log completion audit
            $this->logRolloverCompletion($rolledOverCount);
            
            return $rolledOverCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("performRollover error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 🖥️ Step 3: Display Tasks in UI
     * Function: displayTasks()
     */
    public function getTasksForDisplay($userId, $date, $viewType = 'current') {
        try {
            $today = date('Y-m-d');
            $isToday = ($date === $today);
            $isPastDate = ($date < $today);
            
            if ($isToday) {
                // Logic for Today's View:
                // Show all tasks with task_date = today
                // Include rolled-over tasks (with rolled_from_date IS NOT NULL)
                $stmt = $this->db->prepare("
                    SELECT 
                        id, description, status, priority, sla_hours, task_date,
                        rolled_from_date, source_task_id,
                        CASE 
                            WHEN rolled_from_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', rolled_from_date)
                            ELSE '📋 Today\\'s Task'
                        END as task_indicator,
                        created_at, updated_at
                    FROM tasks 
                    WHERE user_id = ? AND task_date = ?
                    ORDER BY 
                        CASE WHEN rolled_from_date IS NOT NULL THEN 1 ELSE 0 END,
                        CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END
                ");
                $stmt->execute([$userId, $date]);
                
            } elseif ($isPastDate) {
                // Logic for Past Dates:
                // Show only tasks with task_date = [past_date]
                // Tasks completed on [past_date] (based on updated_at)
                // Exclude rolled-over tasks from other dates
                $stmt = $this->db->prepare("
                    SELECT 
                        id, description, status, priority, sla_hours, task_date,
                        rolled_from_date, source_task_id,
                        '📜 Historical View' as task_indicator,
                        created_at, updated_at
                    FROM tasks 
                    WHERE user_id = ? 
                    AND (
                        task_date = ? 
                        OR (status = 'Completed' AND DATE(updated_at) = ?)
                    )
                    AND (rolled_from_date IS NULL OR rolled_from_date != ?)
                    ORDER BY 
                        CASE status WHEN 'Completed' THEN 1 ELSE 2 END,
                        CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END
                ");
                $stmt->execute([$userId, $date, $date, $date]);
                
            } else {
                // Future dates - planning mode
                $stmt = $this->db->prepare("
                    SELECT 
                        id, description, status, priority, sla_hours, task_date,
                        rolled_from_date, source_task_id,
                        '📅 Planned Task' as task_indicator,
                        created_at, updated_at
                    FROM tasks 
                    WHERE user_id = ? AND task_date = ?
                    ORDER BY 
                        CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END
                ");
                $stmt->execute([$userId, $date]);
            }
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Audit Trail: Log UI access
            $this->logViewAccess($userId, $date, count($tasks), $viewType);
            
            return $tasks;
            
        } catch (Exception $e) {
            error_log("getTasksForDisplay error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Auto-rollover trigger for midnight cron and UI access
     */
    public function autoRollover($userId = null) {
        $eligibleTasks = $this->getRolloverTasks($userId);
        
        if (!empty($eligibleTasks)) {
            return $this->performRollover($eligibleTasks);
        }
        
        return 0;
    }
    
    /**
     * Check rollover eligibility based on status management rules
     */
    public function isEligibleForRollover($status) {
        $eligibleStatuses = ['Pending', 'In Progress'];
        return in_array($status, $eligibleStatuses);
    }
    
    private function ensureTasksTable() {
        try {
            // Ensure tasks table has required rollover columns
            $columns = [
                'rolled_from_date' => "ALTER TABLE tasks ADD COLUMN rolled_from_date DATE NULL",
                'source_task_id' => "ALTER TABLE tasks ADD COLUMN source_task_id INT NULL"
            ];
            
            foreach ($columns as $column => $sql) {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM tasks LIKE '{$column}'");
                $stmt->execute();
                if (!$stmt->fetch()) {
                    $this->db->exec($sql);
                }
            }
            
            // Add indexes
            try {
                $this->db->exec("ALTER TABLE tasks ADD INDEX idx_rolled_from_date (rolled_from_date)");
                $this->db->exec("ALTER TABLE tasks ADD INDEX idx_source_task_id (source_task_id)");
            } catch (Exception $e) {
                // Indexes may already exist
            }
            
        } catch (Exception $e) {
            error_log('ensureTasksTable error: ' . $e->getMessage());
        }
    }
    
    private function logRolloverDetection($taskCount, $userId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_value, new_value, notes, created_at)
                VALUES (0, ?, 'rollover_detection', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId ?: 0,
                'system',
                $taskCount,
                "Detected {$taskCount} tasks eligible for rollover"
            ]);
        } catch (Exception $e) {
            error_log('logRolloverDetection error: ' . $e->getMessage());
        }
    }
    
    private function logRolloverCompletion($rolledOverCount) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_value, new_value, notes, created_at)
                VALUES (0, 0, 'rollover_completion', 'system', ?, ?, NOW())
            ");
            $stmt->execute([
                $rolledOverCount,
                "Rollover completed: {$rolledOverCount} tasks rolled over to today"
            ]);
        } catch (Exception $e) {
            error_log('logRolloverCompletion error: ' . $e->getMessage());
        }
    }
    
    private function logTaskHistory($taskId, $userId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_value, new_value, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$taskId, $userId, $action, $oldValue, $newValue, $notes]);
        } catch (Exception $e) {
            error_log('logTaskHistory error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function logViewAccess($userId, $date, $taskCount, $viewType) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_value, new_value, notes, created_at)
                VALUES (0, ?, 'view_access', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $viewType,
                $taskCount,
                "UI access: {$viewType} view for {$date} with {$taskCount} tasks"
            ]);
        } catch (Exception $e) {
            error_log('logViewAccess error: ' . $e->getMessage());
        }
    }
}