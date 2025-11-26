<?php

/**
 * DailyPlanner Model
 * Handles all business logic for the daily task planner, including task fetching,
 * state management (start, pause, resume), and statistics.
 */
class DailyPlanner {
    private $db;
    
    // ⚙️ Configuration Options
    public $autoRollover = true;        // Default: auto rollover enabled
    public $manualTrigger = true;       // Optional button in UI
    public $preserveStatus = true;      // Retain original status
    public $userOptOut = false;         // Allow user to disable per task
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureDailyTasksTable();
    }
    
    private function ensureDailyTasksTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS daily_tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    task_id INT NULL,
                    original_task_id INT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    scheduled_date DATE NOT NULL,
                    planned_start_time TIME NULL,
                    planned_duration INT DEFAULT 60,
                    priority VARCHAR(20) DEFAULT 'medium',
                    status VARCHAR(50) DEFAULT 'not_started',
                    completed_percentage INT DEFAULT 0,
                    start_time TIMESTAMP NULL,
                    pause_time TIMESTAMP NULL,
                    pause_start_time TIMESTAMP NULL,
                    resume_time TIMESTAMP NULL,
                    completion_time TIMESTAMP NULL,
                    sla_end_time TIMESTAMP NULL,
                    active_seconds INT DEFAULT 0,
                    pause_duration INT DEFAULT 0,
                    postponed_from_date DATE NULL,
                    postponed_to_date DATE NULL,
                    source_field VARCHAR(50) NULL,
                    rollover_source_date DATE NULL,
                    rollover_timestamp TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_date (user_id, scheduled_date),
                    INDEX idx_task_id (task_id),
                    INDEX idx_original_task_id (original_task_id),
                    INDEX idx_status (status),
                    INDEX idx_rollover_source (rollover_source_date),
                    INDEX idx_user_task_date (user_id, original_task_id, scheduled_date)
                )
            ");
            
            $this->addMissingColumns();
        } catch (Exception $e) {
            error_log('ensureDailyTasksTable error: ' . $e->getMessage());
        }
    }
    
    private function addMissingColumns() {
        try {
            $columns = [
                'pause_duration' => "ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0",
                'pause_start_time' => "ALTER TABLE daily_tasks ADD COLUMN pause_start_time TIMESTAMP NULL",
                'sla_end_time' => "ALTER TABLE daily_tasks ADD COLUMN sla_end_time TIMESTAMP NULL",
                'resume_time' => "ALTER TABLE daily_tasks ADD COLUMN resume_time TIMESTAMP NULL",
                'active_seconds' => "ALTER TABLE daily_tasks ADD COLUMN active_seconds INT DEFAULT 0",
                'postponed_to_date' => "ALTER TABLE daily_tasks ADD COLUMN postponed_to_date DATE NULL",
                'original_task_id' => "ALTER TABLE daily_tasks ADD COLUMN original_task_id INT NULL",
                'source_field' => "ALTER TABLE daily_tasks ADD COLUMN source_field VARCHAR(50) NULL",
                'rollover_source_date' => "ALTER TABLE daily_tasks ADD COLUMN rollover_source_date DATE NULL",
                'rollover_timestamp' => "ALTER TABLE daily_tasks ADD COLUMN rollover_timestamp TIMESTAMP NULL",
                'remaining_sla_time' => "ALTER TABLE daily_tasks ADD COLUMN remaining_sla_time INT DEFAULT 0",
                'total_pause_duration' => "ALTER TABLE daily_tasks ADD COLUMN total_pause_duration INT DEFAULT 0",
                'overdue_start_time' => "ALTER TABLE daily_tasks ADD COLUMN overdue_start_time TIMESTAMP NULL",
                'time_used' => "ALTER TABLE daily_tasks ADD COLUMN time_used INT DEFAULT 0"
            ];
            
            foreach ($columns as $column => $sql) {
                $result = $this->db->query("SHOW COLUMNS FROM daily_tasks LIKE '{$column}'");
                if (!$result->fetch()) {
                    $this->db->exec($sql);
                }
            }
            
            // Add indexes for timer queries
            try {
                $this->db->exec("ALTER TABLE daily_tasks ADD INDEX idx_status_timer (status, start_time)");
                $this->db->exec("ALTER TABLE daily_tasks ADD INDEX idx_sla_end_time (sla_end_time)");
                $this->db->exec("ALTER TABLE daily_tasks ADD INDEX idx_pause_start_time (pause_start_time)");
            } catch (Exception $e) {
                // Indexes may already exist, ignore errors
            }
        } catch (Exception $e) {
            error_log('addMissingColumns error: ' . $e->getMessage());
        }
    }
    
    public function getTasksForDate($userId, $date) {
        try { // ✅ FIXED: Simplified logic, removed redundant try-catch blocks
            // Step 1: Fetch assigned tasks first
            $this->fetchAssignedTasksForDate($userId, $date);
            
            // Step 2: Simple query to get tasks
            $stmt = $this->db->prepare("
                SELECT 
                    dt.id, dt.title, dt.description, dt.priority, dt.status,
                    dt.completed_percentage, dt.start_time, dt.active_seconds,
                    dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                    dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                    dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date, dt.status,
                    dt.pause_start_time, dt.pause_time, dt.resume_time,
                    COALESCE(t.sla_hours, 0.25) as sla_hours,
                    dt.sla_end_time,
                    CASE 
                        WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
                        WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                        WHEN t.assigned_by != t.assigned_to THEN '👥 From Others'
                        ELSE '👤 Self-Assigned'
                    END as task_indicator,
                    'current_day' as view_type
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.original_task_id = t.id
                WHERE dt.user_id = ? AND dt.scheduled_date = ?
                ORDER BY
                    CASE WHEN dt.rollover_source_date IS NOT NULL THEN 0 ELSE 1 END,
                    CASE dt.status 
                        WHEN 'in_progress' THEN 1 
                        WHEN 'on_break' THEN 2 
                        WHEN 'not_started' THEN 3
                        WHEN 'postponed' THEN 5
                        ELSE 4 
                    END, 
                    CASE dt.priority 
                        WHEN 'high' THEN 1 
                        WHEN 'medium' THEN 2 
                        WHEN 'low' THEN 3 
                        ELSE 4 
                    END
            ");
            $stmt->execute([$userId, $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DailyPlanner getTasksForDate error: " . $e->getMessage());
            return [];
        }
    }
    
    public function fetchAssignedTasksForDate($userId, $date) {
        try {
            $isCurrentDate = ($date === date('Y-m-d'));
            $isPastDate = ($date < date('Y-m-d'));
            $isFutureDate = ($date > date('Y-m-d'));
            
            if ($isPastDate) {
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.assigned_to, t.assigned_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.planned_date = ?
                ");
                $stmt->execute([$userId, $date]);
            } elseif ($isFutureDate) {
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.assigned_to, t.assigned_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND t.planned_date = ?
                ");
                $stmt->execute([$userId, $date]);
            } else {
                // Current date - fetch ONLY tasks with planned_date = today
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.assigned_to, t.assigned_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND t.planned_date = ?
                    ORDER BY 
                        CASE WHEN t.assigned_by != t.assigned_to THEN 1 ELSE 2 END,
                        CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                        t.created_at DESC
                ");
                $stmt->execute([$userId, $date]);
            }
            
            $relevantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $addedCount = 0;
            
            foreach ($relevantTasks as $task) {
                // Check for exact duplicates only
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM daily_tasks 
                    WHERE user_id = ? AND scheduled_date = ? 
                    AND (original_task_id = ? OR (task_id = ? AND original_task_id IS NULL))
                ");
                $checkStmt->execute([$userId, $date, $task['id'], $task['id']]);
                
                if ($checkStmt->fetchColumn() == 0) {
                    $initialStatus = 'not_started';
                    if ($isPastDate && $task['status'] === 'completed') {
                        $initialStatus = 'completed';
                    } elseif ($isFutureDate) {
                        $initialStatus = 'not_started';
                    }
                    
                    $insertStmt = $this->db->prepare("
                        INSERT INTO daily_tasks 
                        (user_id, task_id, original_task_id, title, description, scheduled_date, 
                         priority, status, planned_duration, source_field, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $result = $insertStmt->execute([
                        $userId,
                        $task['id'],
                        $task['id'],
                        $task['title'],
                        $task['description'],
                        $date,
                        $task['priority'],
                        $initialStatus,
                        $task['estimated_duration'] ?: 60,
                        $task['source_field']
                    ]);
                    
                    if ($result) {
                        $addedCount++;
                        
                        try {
                            $this->logTaskHistory(
                                $this->db->lastInsertId(), 
                                $userId, 
                                'fetched', 
                                null, 
                                $task['source_field'], 
                                "📌 Source: {$task['source_field']} on {$date}"
                            );
                        } catch (Exception $e) {
                            error_log('Failed to log task history: ' . $e->getMessage());
                        }
                    } else {
                        error_log('Failed to insert daily task for task ID: ' . $task['id']);
                    }
                }
            }
            
            return $addedCount;
        } catch (Exception $e) {
            error_log("fetchAssignedTasksForDate error: " . $e->getMessage());
            error_log("Error details - User: {$userId}, Date: {$date}");
            return 0;
        }
    }
    
    public function startTask($taskId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get SLA hours for proper end time calculation
            $stmt = $this->db->prepare("
                SELECT dt.*, COALESCE(t.sla_hours, 0.25) as sla_hours 
                FROM daily_tasks dt 
                LEFT JOIN tasks t ON dt.original_task_id = t.id 
                WHERE dt.id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                $this->db->rollback();
                return false;
            }
            
            $now = date('Y-m-d H:i:s');
            $slaSeconds = $task['sla_hours'] * 3600;
            $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $task['sla_hours'] . ' hours'));
            
            // Initialize SLA timer with remaining time
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', start_time = ?, sla_end_time = ?, 
                    remaining_sla_time = ?, updated_at = NOW(),
                    resume_time = NULL, pause_start_time = NULL
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $slaEndTime, $slaSeconds, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logTaskHistory($taskId, $userId, 'started', 'not_started', 'in_progress', 'Task started at ' . $now);
                $this->logTimeAction($taskId, $userId, 'start', $now);
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DailyPlanner startTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function pauseTask($taskId, $userId) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            
            // Get current task state with SLA info
            $stmt = $this->db->prepare("
                SELECT dt.*, COALESCE(t.sla_hours, 0.25) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.original_task_id = t.id
                WHERE dt.id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task || $task['status'] !== 'in_progress') {
                $this->db->rollback();
                return false;
            }
            
            // Calculate remaining SLA time at pause
            $remainingSlaTime = $this->calculateRemainingSlaTime($task);
            $activeTime = $this->calculateActiveTime($taskId);
            
            // Ensure we have a valid remaining SLA time
            if ($remainingSlaTime <= 0 && $task['sla_end_time']) {
                $remainingSlaTime = max(0, strtotime($task['sla_end_time']) - time());
            }
            if ($remainingSlaTime <= 0) {
                $remainingSlaTime = $task['sla_hours'] * 3600; // Fallback to full SLA
            }
            
            // Save remaining SLA time and pause state
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'on_break', pause_start_time = ?, 
                    remaining_sla_time = ?, active_seconds = active_seconds + ?, 
                    time_used = time_used + ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $remainingSlaTime, $activeTime, $activeTime, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logTimeAction($taskId, $userId, 'pause', $now, $activeTime);
                $this->logTaskHistory($taskId, $userId, 'paused', 'in_progress', 'on_break', 'Task paused at ' . $now . ' with ' . $remainingSlaTime . 's remaining');
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DailyPlanner pauseTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function resumeTask($taskId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get current task state with remaining SLA time
            $stmt = $this->db->prepare("
                SELECT pause_start_time, status, remaining_sla_time, total_pause_duration
                FROM daily_tasks WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task || $task['status'] !== 'on_break') {
                $this->db->rollback();
                return false;
            }
            
            $now = date('Y-m-d H:i:s');
            
            // Calculate current pause duration
            $currentPauseDuration = 0;
            if ($task['pause_start_time']) {
                $currentPauseDuration = time() - strtotime($task['pause_start_time']);
            }
            
            // Calculate new SLA end time based on remaining time
            $newSlaEndTime = date('Y-m-d H:i:s', time() + $task['remaining_sla_time']);
            
            // Update task with cumulative pause duration and new SLA end time
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', resume_time = ?, 
                    total_pause_duration = total_pause_duration + ?, 
                    sla_end_time = ?, pause_start_time = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $currentPauseDuration, $newSlaEndTime, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logTimeAction($taskId, $userId, 'resume', $now);
                $this->logTaskHistory($taskId, $userId, 'resumed', 'on_break', 'in_progress', 'Task resumed at ' . $now . ' with ' . $task['remaining_sla_time'] . 's remaining');
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DailyPlanner resumeTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function completeTask($taskId, $userId, $percentage) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            $activeTime = $this->calculateActiveTime($taskId);
            
            // ✅ REBUILT: Consolidated completion logic
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'completed', completion_time = ?, 
                    completed_percentage = ?, active_seconds = active_seconds + ?, 
                    pause_start_time = NULL, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$now, $percentage, $activeTime, $taskId, $userId]);
            
            // Update linked task if exists
            $stmt = $this->db->prepare("
                SELECT original_task_id FROM daily_tasks WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            $originalTaskId = $stmt->fetchColumn();

            if ($originalTaskId) {
                $updateTaskStmt = $this->db->prepare(
                    "UPDATE tasks SET status = 'completed', progress = ? WHERE id = ?"
                );
                $updateTaskStmt->execute([$percentage, $originalTaskId]);
                $this->logTimeAction($taskId, $userId, 'complete', $now, $activeTime);
            }

            // Also update the main task if it exists
            if ($originalTaskId) {
                $updateTaskStmt = $this->db->prepare("UPDATE tasks SET status = 'completed', progress = 100 WHERE id = ?");
                $updateTaskStmt->execute([$originalTaskId]);
            }

            $this->logTaskHistory($taskId, $userId, 'completed', '', $percentage . '%', 'Task completed with ' . $percentage . '% progress');
            $this->updateDailyPerformance($userId, date('Y-m-d'));
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DailyPlanner completeTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTaskProgress($taskId, $userId, $progress, $status, $reason = '') {
        $this->db->beginTransaction();

        try {
            // Get current task data for history
            $stmt = $this->db->prepare("SELECT status, completed_percentage FROM daily_tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                throw new Exception('Task not found');
            }
            
            $oldStatus = $currentData['status'];
            $oldProgress = $currentData['completed_percentage'];
            
            // Determine new status based on progress
            $newStatus = $status;
            if ($progress >= 100) {
                $newStatus = 'completed';
                $completionTime = 'NOW()';
                $activeTime = $this->calculateActiveTime($taskId);
                
                // Update with completion
                $stmt = $this->db->prepare("
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, completion_time = NOW(), 
                        active_seconds = active_seconds + ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $result = $stmt->execute([$newStatus, 100, $activeTime, $taskId, $userId]);
                
                if ($result) {
                    $this->logTimeAction($taskId, $userId, 'complete', date('Y-m-d H:i:s'), $activeTime);
                }
            } else {
                // If progress is updated but not complete, ensure it's 'in_progress'
                $newStatus = ($progress > 0 && $status !== 'on_break') ? 'in_progress' : $status;
                
                // Update progress only
                $stmt = $this->db->prepare("
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $result = $stmt->execute([$newStatus, $progress, $taskId, $userId]);
            }
            
            if (!$result || $stmt->rowCount() === 0) {
                // Do not throw exception, might be a no-op. But rollback.
                $this->db->rollBack();
                return false;
            }
            
            // Update linked task if exists (optional, don't fail if this fails)
            $stmt = $this->db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $originalTaskId = $stmt->fetchColumn();

            if ($originalTaskId) {
                try {
                    $stmt = $this->db->prepare(
                        "UPDATE tasks SET status = ?, progress = ? WHERE id = ?"
                    );
                    $stmt->execute([$newStatus, $progress, $originalTaskId]);
                } catch (Exception $e) {
                    error_log("Failed to update linked task: " . $e->getMessage());
                }
            }
            
            // Log history if status or progress changed
            if ($oldStatus !== $newStatus) {
                $this->logTaskHistory($taskId, $userId, 'status_changed', $oldStatus, $newStatus, $reason);
            }
            if ($oldProgress != $progress) {
                $this->logTaskHistory($taskId, $userId, 'progress_updated', $oldProgress . '%', $progress . '%', $reason);
            }
            
            $this->updateDailyPerformance($userId, date('Y-m-d'));
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("DailyPlanner updateTaskProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    public function postponeTask($taskId, $userId, $newDate) {
        $this->db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            
            // Get current task data with all fields
            $stmt = $this->db->prepare("
                SELECT * FROM daily_tasks WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$taskId, $userId]);
            $currentTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentTask) {
                throw new Exception('Task not found');
            }
            
            // Check if task already exists on target date (excluding postponed tasks)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM daily_tasks 
                WHERE original_task_id = ? AND scheduled_date = ? AND user_id = ? AND status != 'postponed'
            ");
            $stmt->execute([$currentTask['original_task_id'] ?: $currentTask['task_id'], $newDate, $userId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('A task with this content already exists on the target date.');
            }
            
            // Update original task as postponed
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'postponed', postponed_from_date = ?, postponed_to_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$currentTask['scheduled_date'], $newDate, $taskId]);
            
            // ✅ REBUILT: Creates a new, clean entry for the future date, preserving key data.
            $stmt = $this->db->prepare("
                INSERT INTO daily_tasks 
                (user_id, task_id, original_task_id, title, description, scheduled_date, 
                 planned_start_time, planned_duration, priority, status, 
                 completed_percentage, active_seconds, pause_duration,
                 postponed_from_date, source_field, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'not_started', ?, ?, ?, ?, 'postponed', NOW())
            ");
            
            $result = $stmt->execute([
                $currentTask['user_id'],
                $currentTask['task_id'],
                $currentTask['original_task_id'] ?: $currentTask['task_id'],
                $currentTask['title'],
                $currentTask['description'],
                $newDate,
                $currentTask['planned_start_time'],
                $currentTask['planned_duration'],
                $currentTask['priority'],
                $currentTask['completed_percentage'],
                $currentTask['active_seconds'],
                $currentTask['pause_duration'],
                $currentTask['scheduled_date']
            ]);
            
            if ($result) {
                $newTaskId = $this->db->lastInsertId();
                $this->logTimeAction($taskId, $userId, 'postpone', $now);
                $this->logTaskHistory($taskId, $userId, 'postponed', $currentTask['scheduled_date'], $newDate, 'Task postponed to ' . $newDate);
                $this->logTaskHistory($newTaskId, $userId, 'created', null, 'postponed_entry', 'Postponed task entry created for ' . $newDate);
                $this->updateDailyPerformance($userId, $currentTask['scheduled_date']);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                throw new Exception('Failed to create postponed task entry');
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("DailyPlanner postponeTask error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getDailyStats($userId, $date) {
        try {
            // Get stats from today's assigned tasks only
            try {
                $stmt = $this->db->prepare("
                    -- ✅ REBUILT: More accurate and performant stats query.
                    SELECT 
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN status = 'postponed' AND postponed_from_date = ? THEN 1 ELSE 0 END) as postponed_tasks,
                        SUM(CASE WHEN status = 'on_break' THEN 1 ELSE 0 END) as paused_tasks,
                        SUM(planned_duration) as total_planned_minutes,
                        SUM(active_seconds) as total_active_seconds,
                        SUM(pause_duration) as total_pause_seconds,
                        AVG(completed_percentage) as avg_completion
                    FROM daily_tasks 
                    WHERE user_id = ? AND scheduled_date = ?
                ");
                $stmt->execute([$date, $userId, $date]);
                $dailyStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate SLA totals from today's assigned tasks only
                $stmt = $this->db->prepare("
                    SELECT SUM(COALESCE(t.sla_hours, 0.25) * 3600) as total_sla_seconds
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ?
                ");
                $stmt->execute([$userId, $date]);
                $slaData = $stmt->fetch(PDO::FETCH_ASSOC);
                $dailyStats['total_sla_seconds'] = $slaData['total_sla_seconds'] ?? 0;
                
            } catch (Exception $e) {
                error_log('Daily stats complex query failed, using fallback: ' . $e->getMessage());
                $stmt = $this->db->prepare("SELECT COUNT(*) as total_tasks FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                $stmt->execute([$userId, $date]);
                $dailyStats = ['total_tasks' => $stmt->fetchColumn(), 'completed_tasks' => 0, 'in_progress_tasks' => 0, 'postponed_tasks' => 0, 'total_planned_minutes' => 0, 'total_active_seconds' => 0, 'total_pause_seconds' => 0, 'total_sla_seconds' => 0, 'avg_completion' => 0];
            }
            
            // Add postponed tasks count from other dates
            if ($dailyStats) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as postponed_count
                    FROM daily_tasks 
                    WHERE user_id = ? AND status = 'postponed' AND postponed_from_date = ?
                ");
                $stmt->execute([$userId, $date]);
                $postponedCount = $stmt->fetchColumn();
                $dailyStats['postponed_tasks'] = $postponedCount;
            }
            
            // If no daily tasks stats, get from regular tasks
            if (empty($dailyStats['total_tasks'])) {
                try {
                    $stmt = $this->db->prepare("
                        SELECT 
                            COUNT(*) as total_tasks,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                            0 as postponed_tasks,
                            SUM(COALESCE(sla_hours * 60, estimated_duration, 60)) as total_planned_minutes,
                            0 as total_active_seconds,
                            AVG(COALESCE(progress, 0)) as avg_completion
                        FROM tasks 
                        WHERE assigned_to = ? 
                        AND (
                            DATE(created_at) = ? OR
                            DATE(deadline) = ? OR
                            DATE(planned_date) = ? OR
                            status = 'in_progress' OR
                            (assigned_by != assigned_to AND DATE(COALESCE(assigned_at, created_at)) = ?)
                        )
                        AND status != 'completed'
                    ");
                    $stmt->execute([$userId, $date, $date, $date, $date]);
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log('Regular tasks stats query failed, using simple fallback: ' . $e->getMessage());
                    $stmt = $this->db->prepare("SELECT COUNT(*) as total_tasks FROM tasks WHERE assigned_to = ?");
                    $stmt->execute([$userId]);
                    return ['total_tasks' => $stmt->fetchColumn(), 'completed_tasks' => 0, 'in_progress_tasks' => 0, 'postponed_tasks' => 0, 'total_planned_minutes' => 0, 'total_active_seconds' => 0, 'avg_completion' => 0];
                }
            }
            
            return $dailyStats;
        } catch (Exception $e) {
            error_log("DailyPlanner getDailyStats error: " . $e->getMessage());
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'postponed_tasks' => 0,
                'total_planned_minutes' => 0,
                'total_active_seconds' => 0,
                'avg_completion' => 0
            ];
        }
    }
    
    private function calculateActiveTime($taskId) {
        try {
            $stmt = $this->db->prepare("
                SELECT start_time, resume_time, status, active_seconds
                FROM daily_tasks 
                WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task || !in_array($task['status'], ['in_progress', 'on_break'])) {
                return 0;
            }

            // Use resume_time if available, otherwise start_time
            $referenceTime = $task['resume_time'] ?: $task['start_time'];
            if (!$referenceTime) return 0;
            
            // Calculate time since last start/resume
            $currentSessionTime = max(0, time() - strtotime($referenceTime));
            
            return max(0, $currentSessionTime);
        } catch (Exception $e) {
            error_log("calculateActiveTime error: " . $e->getMessage());
            return 0;
        }
    }
    
    private function calculateRemainingSlaTime($task) {
        try {
            $now = time();
            
            // If task has remaining_sla_time saved (from previous pause), use it
            if ($task['remaining_sla_time'] > 0) {
                return $task['remaining_sla_time'];
            }
            
            // Calculate from SLA end time
            if ($task['sla_end_time']) {
                $slaEndTimestamp = strtotime($task['sla_end_time']);
                $remaining = max(0, $slaEndTimestamp - $now);
                return $remaining;
            }
            
            // Fallback: calculate from SLA hours
            $slaSeconds = $task['sla_hours'] * 3600;
            $startTime = strtotime($task['start_time']);
            $elapsed = $now - $startTime;
            
            return max(0, $slaSeconds - $elapsed);
        } catch (Exception $e) {
            error_log("calculateRemainingSlaTime error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function startOverdueTimer($taskId) {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET overdue_start_time = ?
                WHERE id = ? AND overdue_start_time IS NULL
            ");
            $stmt->execute([$now, $taskId]);
            return true;
        } catch (Exception $e) {
            error_log("startOverdueTimer error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logTimeAction($taskId, $userId, $action, $timestamp, $duration = 0) {
        $notes = "Action: {$action} at {$timestamp}. Duration: {$duration}s.";
        $this->logTaskHistory($taskId, $userId, "time_{$action}", $duration, null, $notes);
    }
    
    // REMOVED: ensureTimeLogsTable() is no longer needed as the table is deprecated.
    
    public function getTaskHistory($taskId, $userId) {
        try {
            
            $stmt = $this->db->prepare("
                SELECT h.*, u.name as user_name 
                FROM daily_task_history h 
                LEFT JOIN users u ON h.created_by = u.id 
                WHERE h.daily_task_id = ? 
                ORDER BY h.created_at DESC
            ");
            $stmt->execute([$taskId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($entry) {
                return [
                    'date' => date('M d, Y H:i', strtotime($entry['created_at'])),
                    'action' => $this->formatActionText($entry['action']),
                    'progress' => $this->extractProgressFromValue($entry['new_value']),
                    'user' => $entry['user_name'] ?? 'System',
                    'notes' => $entry['notes']
                ];
            }, $history);
        } catch (Exception $e) {
            error_log("getTaskHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    private function logTaskHistory($taskId, $userId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO daily_task_history (daily_task_id, action, old_value, new_value, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$taskId, $action, $oldValue, $newValue, $notes, $userId]);
        } catch (Exception $e) {
            error_log('Daily task history log error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function formatActionText($action) {
        return match($action) {
            'created' => 'Task Created',
            'status_changed' => 'Status Changed',
            'progress_updated' => 'Progress Updated',
            'assigned' => 'Task Assigned',
            'completed' => 'Task Completed',
            'cancelled' => 'Task Cancelled',
            'updated' => 'Task Updated',
            'commented' => 'Comment Added',
            'rolled_over' => 'Rolled Over',
            'postponed' => 'Postponed',
            default => ucfirst(str_replace('_', ' ', $action))
        };
    }
    
    private function extractProgressFromValue($value) {
        if (strpos($value, '%') !== false) {
            return intval(str_replace('%', '', $value));
        }
        return 0;
    }
    
    public function updateDailyPerformance($userId, $date) {
        try {
            $stats = $this->getDailyStats($userId, $date);
            
            $completionPercentage = $stats['total_tasks'] > 0 
                ? ($stats['completed_tasks'] / $stats['total_tasks']) * 100 
                : 0;
            
            $stmt = $this->db->prepare("
                INSERT INTO daily_performance 
                (user_id, date, total_planned_minutes, total_active_minutes, total_tasks, 
                 completed_tasks, in_progress_tasks, postponed_tasks, completion_percentage)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                total_planned_minutes = VALUES(total_planned_minutes),
                total_active_minutes = VALUES(total_active_minutes),
                total_tasks = VALUES(total_tasks),
                completed_tasks = VALUES(completed_tasks),
                in_progress_tasks = VALUES(in_progress_tasks),
                postponed_tasks = VALUES(postponed_tasks),
                completion_percentage = VALUES(completion_percentage)
            ");
            
            $stmt->execute([
                $userId, $date, 
                $stats['total_planned_minutes'] ?: 0,
                round(($stats['total_active_seconds'] ?: 0) / 60, 2),
                $stats['total_tasks'] ?: 0,
                $stats['completed_tasks'] ?: 0,
                $stats['in_progress_tasks'] ?: 0,
                $stats['postponed_tasks'] ?: 0,
                $completionPercentage
            ]);
        } catch (Exception $e) {
            error_log("updateDailyPerformance error: " . $e->getMessage());
        }
    }
    
    /**
     * 🔁 Step 1: Detect Eligible Tasks for Rollover
     * Function: getRolloverTasks()
     */
    public function getRolloverTasks($userId = null) {
        try {
            $today = date('Y-m-d');
            
            // Query daily_tasks where:
            // - scheduled_date < today
            // - status IN ('not_started', 'in_progress', 'on_break')
            // - rollover_source_date IS NULL (not already rolled over)
            $whereClause = "scheduled_date < ? AND status IN ('not_started', 'in_progress', 'on_break') AND completed_percentage < 100";
            $params = [$today];
            
            // User-specific filtering
            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            // Exclude tasks already rolled over
            $whereClause .= " AND NOT EXISTS (
                SELECT 1 FROM daily_tasks dt2 
                WHERE dt2.original_task_id = daily_tasks.original_task_id 
                AND dt2.scheduled_date = ? 
                AND dt2.rollover_source_date IS NOT NULL
            )";
            $params[] = $today;
            
            $stmt = $this->db->prepare("SELECT * FROM daily_tasks WHERE {$whereClause}");
            $stmt->execute($params);
            $eligibleTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Audit Trail: Log detection
            foreach ($eligibleTasks as $task) {
                $this->logTaskHistory(
                    $task['id'],
                    $task['user_id'],
                    'rollover_detected',
                    $task['scheduled_date'],
                    $today,
                    "Task detected for rollover from {$task['scheduled_date']}"
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
    public function performRollover($eligibleTasks = null, $userId = null) {
        try {
            if ($eligibleTasks === null) {
                $eligibleTasks = $this->getRolloverTasks($userId);
            }
            
            $today = date('Y-m-d');
            $rolledOverCount = 0;
            
            $this->db->beginTransaction();
            
            foreach ($eligibleTasks as $task) {
                // Check for duplicates
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM daily_tasks 
                    WHERE user_id = ? AND original_task_id = ? AND scheduled_date = ? AND rollover_source_date IS NOT NULL
                ");
                $checkStmt->execute([$task['user_id'], $task['original_task_id'] ?: $task['task_id'], $today]);
                
                if ($checkStmt->fetchColumn() == 0) {
                    // Create new rollover entry
                    $stmt = $this->db->prepare("
                        INSERT INTO daily_tasks 
                        (user_id, task_id, original_task_id, title, description, scheduled_date, 
                         planned_start_time, planned_duration, priority, status, 
                         completed_percentage, active_seconds, pause_duration,
                         rollover_source_date, rollover_timestamp, source_field)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'rollover')
                    ");
                    
                    // Preserve original data but reset status based on config
                    $newStatus = $this->preserveStatus ? $task['status'] : 'not_started';
                    
                    $result = $stmt->execute([
                        $task['user_id'],
                        $task['task_id'],
                        $task['original_task_id'] ?: $task['task_id'],
                        $task['title'],
                        $task['description'],
                        $today,
                        $task['planned_start_time'],
                        $task['planned_duration'],
                        $task['priority'],
                        $newStatus,
                        $task['completed_percentage'],
                        $task['active_seconds'],
                        $task['pause_duration'],
                        $task['scheduled_date']
                    ]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $newTaskId = $this->db->lastInsertId();
                        
                        // Update original task status (mark as rolled over)
                        $updateStmt = $this->db->prepare("
                            UPDATE daily_tasks 
                            SET status = 'rolled_over', updated_at = NOW() 
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
                            "🔄 Rolled over from: {$task['scheduled_date']}"
                        );
                        
                        $rolledOverCount++;
                    }
                }
            }
            
            $this->db->commit();
            return $rolledOverCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("performRollover error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Logs UI view access for audit trail purposes.
     */
    private function logViewAccess($userId, $date, $taskCount, $viewType = 'current') {
        try {
            $this->ensureAuditTable();
            $action = ($viewType === 'historical') ? 'historical_view_access' : 'view_access';
            $details = json_encode([
                'view_type' => $viewType,
                'task_count' => $taskCount,
                'date_accessed' => date('Y-m-d H:i:s')
            ]);
            
            $stmt = $this->db->prepare("
                INSERT INTO daily_planner_audit 
                (user_id, action, target_date, task_count, details, timestamp)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $action, $date, $taskCount, $details]);
        } catch (Exception $e) {
            error_log("logViewAccess error: " . $e->getMessage());
        }
    }
    
    private function ensureAuditTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS daily_planner_audit (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    target_date DATE NULL,
                    task_count INT DEFAULT 0,
                    details TEXT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_action (user_id, action),
                    INDEX idx_date (target_date)
                )
            ");
        } catch (Exception $e) {
            error_log('ensureAuditTable error: ' . $e->getMessage());
        }
    }
    
    public function cleanupDuplicateTasks($userId = null, $date = null) {
        try {
            $whereClause = "";
            $params = [];
            
            if ($userId) {
                $whereClause .= " AND dt1.user_id = ?";
                $params[] = $userId;
            }
            
            if ($date) {
                $whereClause .= " AND dt1.scheduled_date = ?";
                $params[] = $date;
            }
            
            // FIXED: Correct SQL DELETE self-join using proper ON clause syntax
            $stmt = $this->db->prepare("
                DELETE dt1 FROM daily_tasks dt1
                INNER JOIN daily_tasks dt2 
                ON dt1.user_id = dt2.user_id 
                   AND dt1.original_task_id = dt2.original_task_id 
                   AND dt1.scheduled_date = dt2.scheduled_date
                   AND dt1.id > dt2.id
                {$whereClause}
            ");
            $stmt->execute($params);
            
            $deletedCount = $stmt->rowCount();
            if ($deletedCount > 0) {
                error_log("Cleaned up {$deletedCount} duplicate daily tasks");
            }
            
            return $deletedCount;
        } catch (Exception $e) {
            error_log("cleanupDuplicateTasks error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 📋 Status Management Rules
     */
    public function isEligibleForRollover($status) {
        $eligibleStatuses = ['not_started', 'in_progress', 'on_break'];
        return in_array($status, $eligibleStatuses);
    }
    
    /**
     * Check if task should continue rolling over
     */
    public function shouldContinueRollover($status, $completedPercentage) {
        // Stop rollover if task is completed or postponed
        if (in_array($status, ['completed', 'postponed', 'cancelled'])) {
            return false;
        }
        
        // Stop rollover if task is 100% complete
        if ($completedPercentage >= 100) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Auto-rollover with configuration support
     */
    public function autoRollover($userId = null) {
        if (!$this->autoRollover) {
            return 0;
        }
        
        $eligibleTasks = $this->getRolloverTasks($userId);
        
        if (!empty($eligibleTasks)) {
            return $this->performRollover($eligibleTasks, $userId);
        }
        
        return 0;
    }
    
    /**
     * Manual rollover trigger for UI
     */
    public function manualRolloverTrigger($userId) {
        if (!$this->manualTrigger) {
            throw new Exception('Manual rollover is disabled');
        }
        
        return $this->autoRollover($userId);
    }
    
    /**
     * Schedule automatic rollover via cron job
     */
    public static function scheduleAutoRollover() {
        // This method can be called by a cron job daily at midnight
        return self::runDailyRollover();
    }
    
    public static function runDailyRollover() {
        try {
            $planner = new DailyPlanner();
            
            // Clean up duplicates first
            $cleanedCount = $planner->cleanupDuplicateTasks();
            
            // ✅ USE SPEC-COMPLIANT ROLLOVER: Get all eligible tasks for all users.
            $eligibleTasks = $planner->getRolloverTasks(); // Pass no user ID to get all.
            $totalRolledOver = $planner->performRollover($eligibleTasks);
            
            // Log rollover completion with audit compliance
            $planner->db->prepare("
                INSERT INTO daily_planner_audit 
                (user_id, action, task_count, details, timestamp)
                VALUES (0, 'daily_rollover', ?, ?, NOW())
            ")->execute([
                $totalRolledOver, 
                json_encode([
                    'instruction_name' => 'AutoRolloverTasksToToday',
                    'execution_context' => 'DailyPlanner → UnifiedWorkflowController',
                    'cleaned_duplicates' => $cleanedCount,
                    'rolled_over_tasks' => $totalRolledOver
                ])
            ]);
            
            error_log("Daily rollover completed: {$totalRolledOver} tasks rolled over, {$cleanedCount} duplicates cleaned");
            return $totalRolledOver;
            
        } catch (Exception $e) {
            error_log("Daily rollover failed: " . $e->getMessage());
            return 0;
        }
    }
}
