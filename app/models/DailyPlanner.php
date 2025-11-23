<?php

class DailyPlanner {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureDailyTasksTable();
        $this->ensureTaskHistoryTable();
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
                    UNIQUE KEY unique_task_date (user_id, original_task_id, scheduled_date)
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
                'pause_duration' => "ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0 AFTER active_seconds",
                'pause_start_time' => "ALTER TABLE daily_tasks ADD COLUMN pause_start_time TIMESTAMP NULL AFTER pause_time",
                'postponed_to_date' => "ALTER TABLE daily_tasks ADD COLUMN postponed_to_date DATE NULL AFTER postponed_from_date",
                'original_task_id' => "ALTER TABLE daily_tasks ADD COLUMN original_task_id INT NULL AFTER task_id",
                'source_field' => "ALTER TABLE daily_tasks ADD COLUMN source_field VARCHAR(50) NULL AFTER postponed_to_date",
                'rollover_source_date' => "ALTER TABLE daily_tasks ADD COLUMN rollover_source_date DATE NULL AFTER source_field",
                'rollover_timestamp' => "ALTER TABLE daily_tasks ADD COLUMN rollover_timestamp TIMESTAMP NULL AFTER rollover_source_date"
            ];
            
            foreach ($columns as $column => $sql) {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM daily_tasks LIKE '{$column}'");
                $stmt->execute();
                if (!$stmt->fetch()) {
                    $this->db->exec($sql);
                }
            }
            
            // Add indexes if missing
            try {
                $this->db->exec("ALTER TABLE daily_tasks ADD INDEX idx_original_task_id (original_task_id)");
                $this->db->exec("ALTER TABLE daily_tasks ADD INDEX idx_rollover_source (rollover_source_date)");
                $this->db->exec("ALTER TABLE daily_tasks ADD UNIQUE KEY unique_task_date (user_id, original_task_id, scheduled_date)");
            } catch (Exception $e) {
                // Indexes may already exist, ignore errors
            }
        } catch (Exception $e) {
            error_log('addMissingColumns error: ' . $e->getMessage());
        }
    }
    
    public function getTasksForDate($userId, $date) {
        try {
            $isCurrentDate = ($date === date('Y-m-d'));
            $isPastDate = ($date < date('Y-m-d'));
            $isFutureDate = ($date > date('Y-m-d'));
            
            // Step 1: Fetch assigned/planned tasks for the specified date
            // CORRECTED COMMENT: fetchAssignedTasksForDate is called unconditionally to ensure data consistency
            $this->fetchAssignedTasksForDate($userId, $date);
            
            // Step 2: Auto-rollover only for current date with user-specific filtering
            if ($isCurrentDate) {
                // Check if rollover has already been done today for this user
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM daily_tasks 
                    WHERE user_id = ? AND scheduled_date = ? AND rollover_source_date = ?
                ");
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $stmt->execute([$userId, $date, $yesterday]);
                $alreadyRolledOver = $stmt->fetchColumn() > 0;
                
                if (!$alreadyRolledOver) {
                    $this->rolloverUncompletedTasks($yesterday, $userId);
                }
            }
            
            // Step 3: Get tasks with audit trail and visual indicators
            if ($isCurrentDate) {
                // Current date: show all tasks including rollovers
                $stmt = $this->db->prepare("
                    SELECT 
                        dt.id, dt.title, dt.description, dt.priority, dt.status,
                        dt.completed_percentage, dt.start_time, dt.active_seconds,
                        dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                        dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                        dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                        COALESCE(t.sla_hours, 0.25) as sla_hours,
                        CASE 
                            WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
                            WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                            WHEN t.assigned_by != t.assigned_to THEN '👥 From Others'
                            ELSE '👤 Self-Assigned'
                        END as task_indicator
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ?
                    ORDER BY 
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
            } elseif ($isFutureDate) {
                // Future date: show only tasks specifically planned for that date (planning mode)
                $stmt = $this->db->prepare("
                    SELECT 
                        dt.id, dt.title, dt.description, dt.priority, dt.status,
                        dt.completed_percentage, dt.start_time, dt.active_seconds,
                        dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                        dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                        dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                        COALESCE(t.sla_hours, 0.25) as sla_hours,
                        CASE 
                            WHEN dt.source_field = 'planned_date' THEN '📅 Planned for this date'
                            WHEN dt.source_field = 'deadline' THEN '⏰ Deadline on this date'
                            WHEN dt.postponed_to_date = ? THEN '🔄 Postponed to this date'
                            ELSE '📋 Planning Mode'
                        END as task_indicator,
                        'planning' as view_mode
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ?
                    ORDER BY 
                        CASE dt.priority 
                            WHEN 'high' THEN 1 
                            WHEN 'medium' THEN 2 
                            WHEN 'low' THEN 3 
                            ELSE 4 
                        END,
                        dt.created_at ASC
                ");
                $stmt->execute([$date, $userId, $date]);
            } else {
                // Past date: show all tasks that were assigned to that specific date
                $stmt = $this->db->prepare("
                    SELECT 
                        dt.id, dt.title, dt.description, dt.priority, dt.status,
                        dt.completed_percentage, dt.start_time, dt.active_seconds,
                        dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                        dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                        dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                        COALESCE(t.sla_hours, 0.25) as sla_hours,
                        CASE 
                            WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
                            WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                            ELSE '📜 Historical View'
                        END as task_indicator,
                        'historical' as view_mode
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ?
                    ORDER BY 
                        CASE dt.status 
                            WHEN 'completed' THEN 1
                            WHEN 'in_progress' THEN 2 
                            WHEN 'not_started' THEN 3
                            WHEN 'postponed' THEN 4
                            ELSE 5 
                        END, 
                        CASE dt.priority 
                            WHEN 'high' THEN 1 
                            WHEN 'medium' THEN 2 
                            WHEN 'low' THEN 3 
                            ELSE 4 
                        END,
                        dt.created_at ASC
                ");
                $stmt->execute([$userId, $date]);
            }
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log view access for audit trail with context
            $viewType = $isCurrentDate ? 'current' : ($isFutureDate ? 'planning' : 'historical');
            $this->logViewAccess($userId, $date, count($tasks), $viewType);
            
            return $tasks;
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
                // FIXED: For past dates, show tasks that were planned/assigned for that specific date
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.planned_date = ?
                    
                    UNION ALL
                    
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'deadline' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND DATE(t.deadline) = ?
                    AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00')
                    
                    UNION ALL
                    
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'created_at' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND DATE(t.created_at) = ?
                    AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00')
                    AND (t.deadline IS NULL OR DATE(t.deadline) != ?)
                ");
                $stmt->execute([$userId, $date, $userId, $date, $userId, $date, $date]);
            } elseif ($isFutureDate) {
                // SIMPLIFIED: For future dates, prioritize planned_date matching
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND t.planned_date = ?
                    
                    UNION ALL
                    
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'deadline' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND DATE(t.deadline) = ?
                    AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00')
                ");
                $stmt->execute([$userId, $date, $userId, $date]);
            } else {
                // FIXED: For current date, prioritize planned_date and only show tasks on their specific planned date
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        CASE 
                            WHEN DATE(t.planned_date) = ? THEN 'planned_date'
                            WHEN DATE(t.deadline) = ? THEN 'deadline'
                            WHEN DATE(t.created_at) = ? THEN 'created_at'
                            WHEN DATE(t.updated_at) = ? THEN 'updated_at'
                            ELSE 'other'
                        END as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed')
                    AND (
                        -- PRIORITY 1: Tasks with planned_date matching the requested date
                        DATE(t.planned_date) = ? OR
                        -- PRIORITY 2: Tasks with deadline on this date but no planned_date
                        (DATE(t.deadline) = ? AND t.planned_date IS NULL) OR
                        -- PRIORITY 3: Tasks created today with no planned_date or deadline (only for current date)
                        (? = CURDATE() AND DATE(t.created_at) = ? AND t.planned_date IS NULL AND t.deadline IS NULL)
                    )
                ");
                $stmt->execute([$date, $date, $date, $date, $userId, $date, $date, $date, $date]);
            }
            
            $relevantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $addedCount = 0;
            
            // Process each relevant task
            foreach ($relevantTasks as $task) {
                // Check if task already exists in daily_tasks for this date
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM daily_tasks 
                    WHERE user_id = ? AND original_task_id = ? AND scheduled_date = ?
                ");
                $checkStmt->execute([$userId, $task['id'], $date]);
                
                if ($checkStmt->fetchColumn() == 0) {
                    // Set initial status based on task status and date
                    $initialStatus = 'not_started';
                    if ($isPastDate && $task['status'] === 'completed') {
                        $initialStatus = 'completed';
                    } elseif ($isFutureDate) {
                        // Future tasks are always not_started initially
                        $initialStatus = 'not_started';
                    }
                    
                    // Insert into daily_tasks
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
                        
                        // Log audit trail (optional, don't fail if this fails)
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
            $now = date('Y-m-d H:i:s');
            
            // Simple update without transaction for debugging
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', start_time = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logTaskHistory($taskId, $userId, 'started', 'not_started', 'in_progress', 'Task started at ' . $now);
                $this->logTimeAction($taskId, $userId, 'start', $now);
            }
            
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("DailyPlanner startTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function pauseTask($taskId, $userId) {
        try {
            $now = date('Y-m-d H:i:s');
            
            // Calculate active time since start/resume
            $activeTime = $this->calculateActiveTime($taskId);
            
            // Update daily_tasks with pause start time
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'on_break', pause_time = ?, pause_start_time = ?,
                    active_seconds = active_seconds + ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $now, $activeTime, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logTimeAction($taskId, $userId, 'pause', $now, $activeTime);
                $this->logTaskHistory($taskId, $userId, 'paused', 'in_progress', 'on_break', 'Task paused at ' . $now);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("DailyPlanner pauseTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function resumeTask($taskId, $userId) {
        try {
            // Validate inputs
            if (!$taskId || !$userId) {
                throw new Exception("Task ID and User ID are required");
            }
            
            // Check if task exists - allow any user to resume any task
            $stmt = $this->db->prepare("
                SELECT id, status, title 
                FROM daily_tasks 
                WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                throw new Exception("Task not found or access denied");
            }
            
            // Check if task can be resumed
            if (!in_array($task['status'], ['on_break'])) {
                throw new Exception("Task cannot be resumed. Current status: " . $task['status']);
            }
            
            $now = date('Y-m-d H:i:s');
            
            // Calculate pause duration if pause_start_time exists
            $stmt = $this->db->prepare("SELECT pause_start_time, pause_duration FROM daily_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $pauseData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $additionalPauseDuration = 0;
            if ($pauseData && $pauseData['pause_start_time']) {
                $additionalPauseDuration = time() - strtotime($pauseData['pause_start_time']);
            }
            
            // Update task status and add pause duration
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', resume_time = ?, 
                    pause_duration = pause_duration + ?, pause_start_time = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$now, $additionalPauseDuration, $taskId]);
            
            if (!$result) {
                throw new Exception("Failed to update task status");
            }
            
            // Verify the update was successful
            if ($stmt->rowCount() === 0) {
                throw new Exception("No rows were updated. Task may not exist or belong to user.");
            }
            
            // Log the action (with error handling)
            try {
                $this->logTimeAction($taskId, $userId, 'resume', $now);
            } catch (Exception $e) {
                error_log("Failed to log time action: " . $e->getMessage());
            }
            
            try {
                $this->logTaskHistory($taskId, $userId, 'resumed', 'paused', 'in_progress', 'Task resumed at ' . $now);
            } catch (Exception $e) {
                error_log("Failed to log task history: " . $e->getMessage());
            }
            
            return true;
            
            
        } catch (Exception $e) {
            error_log("DailyPlanner resumeTask error: " . $e->getMessage());
            throw $e; // Re-throw to provide specific error message
        }
    }
    
    public function completeTask($taskId, $userId, $percentage) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            $activeTime = $this->calculateActiveTime($taskId);
            
            // Update daily_tasks
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'completed', completion_time = ?, 
                    completed_percentage = ?, active_seconds = active_seconds + ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$now, $percentage, $activeTime, $taskId, $userId]);
            
            // Update linked task if exists
            $stmt = $this->db->prepare("
                UPDATE tasks t 
                JOIN daily_tasks dt ON t.id = dt.task_id
                SET t.status = 'completed', t.progress = ?, t.actual_time_seconds = dt.active_seconds
                WHERE dt.id = ?
            ");
            $stmt->execute([$percentage, $taskId]);
            
            $this->logTimeAction($taskId, $userId, 'complete', $now, $activeTime);
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
            if ($progress >= 100) {
                $newStatus = 'completed';
                $activeTime = $this->calculateActiveTime($taskId);
                
                // Update with completion
                $stmt = $this->db->prepare("
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, completion_time = NOW(), 
                        active_seconds = active_seconds + ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $result = $stmt->execute([$newStatus, $progress, $activeTime, $taskId, $userId]);
                
                if ($result) {
                    $this->logTimeAction($taskId, $userId, 'complete', date('Y-m-d H:i:s'), $activeTime);
                }
            } else {
                $newStatus = $progress > 0 ? 'in_progress' : 'assigned';
                
                // Update progress only
                $stmt = $this->db->prepare("
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $result = $stmt->execute([$newStatus, $progress, $taskId, $userId]);
            }
            
            if (!$result || $stmt->rowCount() === 0) {
                throw new Exception('Failed to update task progress');
            }
            
            // Update linked task if exists (optional, don't fail if this fails)
            try {
                $stmt = $this->db->prepare("
                    UPDATE tasks t 
                    JOIN daily_tasks dt ON t.id = dt.task_id
                    SET t.status = ?, t.progress = ?
                    WHERE dt.id = ?
                ");
                $stmt->execute([$newStatus, $progress, $taskId]);
            } catch (Exception $e) {
                error_log("Failed to update linked task: " . $e->getMessage());
            }
            
            // Log history if status or progress changed
            if ($oldStatus !== $newStatus) {
                $this->logTaskHistory($taskId, $userId, 'status_changed', $oldStatus, $newStatus, $reason);
            }
            if ($oldProgress != $progress) {
                $this->logTaskHistory($taskId, $userId, 'progress_updated', $oldProgress . '%', $progress . '%', $reason);
            }
            
            $this->updateDailyPerformance($userId, date('Y-m-d'));
            
            return true;
        } catch (Exception $e) {
            error_log("DailyPlanner updateTaskProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    public function postponeTask($taskId, $userId, $newDate) {
        try {
            // Add missing columns first
            $this->addPostponeColumns();
            
            $now = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');
            
            // Get current task data
            $stmt = $this->db->prepare("SELECT scheduled_date, postponed_to_date, status FROM daily_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentTask) {
                throw new Exception('Task not found');
            }
            
            // Check if already postponed to the same date
            if ($currentTask['postponed_to_date'] === $newDate) {
                throw new Exception('This task is already postponed to this date.');
            }
            
            // Check if task already exists on target date to prevent duplicates
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM daily_tasks 
                WHERE task_id = (SELECT task_id FROM daily_tasks WHERE id = ?) 
                AND scheduled_date = ? AND user_id = ?
            ");
            $stmt->execute([$taskId, $newDate, $userId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('A task with this content already exists on the target date.');
            }
            
            $originalDate = $currentTask['scheduled_date'];
            
            // Update the task with postponed status but keep on original date
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'postponed', postponed_from_date = ?, postponed_to_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$originalDate, $newDate, $taskId]);
            
            if ($result) {
                $this->logTimeAction($taskId, $userId, 'postpone', $now);
                $this->logTaskHistory($taskId, $userId, 'postponed', $originalDate, $newDate, 'Task postponed to ' . $newDate);
                $this->updateDailyPerformance($userId, $originalDate);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("DailyPlanner postponeTask error: " . $e->getMessage());
            throw $e; // Re-throw to show specific error message
        }
    }
    
    private function addPostponeColumns() {
        try {
            // Add postponed_to_date column if missing
            $stmt = $this->db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'postponed_to_date'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE daily_tasks ADD COLUMN postponed_to_date DATE NULL");
            }
        } catch (Exception $e) {
            error_log('Add postpone columns error: ' . $e->getMessage());
        }
    }
    
    public function getDailyStats($userId, $date) {
        try {
            // Get stats from today's assigned tasks only
            try {
                $stmt = $this->db->prepare("
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
                    SELECT SUM(COALESCE(t.sla_hours, 1) * 3600) as total_sla_seconds
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ? AND DATE(dt.created_at) = ?
                ");
                $stmt->execute([$userId, $date, $date]);
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
                SELECT start_time, resume_time, status 
                FROM daily_tasks 
                WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task || $task['status'] === 'paused') return 0;
            
            $startTime = $task['resume_time'] ?: $task['start_time'];
            if (!$startTime) return 0;
            
            return time() - strtotime($startTime);
        } catch (Exception $e) {
            error_log("calculateActiveTime error: " . $e->getMessage());
            return 0;
        }
    }
    
    private function logTimeAction($taskId, $userId, $action, $timestamp, $duration = 0) {
        try {
            // Ensure time_logs table exists
            $this->ensureTimeLogsTable();
            
            $stmt = $this->db->prepare("
                INSERT INTO time_logs (daily_task_id, user_id, action, timestamp, active_duration)
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$taskId, $userId, $action, $timestamp, $duration]);
        } catch (Exception $e) {
            error_log("logTimeAction error: " . $e->getMessage());
            // Don't throw exception to avoid breaking the main operation
        }
    }
    
    private function ensureTimeLogsTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS time_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    daily_task_id INT NOT NULL,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    timestamp TIMESTAMP NOT NULL,
                    active_duration INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_daily_task_id (daily_task_id),
                    INDEX idx_user_id (user_id)
                )
            ");
        } catch (Exception $e) {
            error_log('ensureTimeLogsTable error: ' . $e->getMessage());
        }
    }
    
    public function getTaskHistory($taskId, $userId) {
        try {
            $this->ensureTaskHistoryTable();
            
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
            $this->ensureTaskHistoryTable();
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
    
    private function ensureTaskHistoryTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS daily_task_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    daily_task_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    old_value TEXT,
                    new_value TEXT,
                    notes TEXT,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_daily_task_id (daily_task_id)
                )
            ");
            
            // Also ensure sla_history table has postpone tracking
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS sla_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    daily_task_id INT NOT NULL,
                    action VARCHAR(20) NOT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    duration_seconds INT DEFAULT 0,
                    notes TEXT,
                    INDEX idx_daily_task_id (daily_task_id)
                )
            ");
        } catch (Exception $e) {
            error_log('ensureTaskHistoryTable error: ' . $e->getMessage());
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
    
    public function rolloverUncompletedTasks($targetDate = null, $userId = null) {
        try {
            $yesterday = $targetDate ?: date('Y-m-d', strtotime('-1 day'));
            $today = date('Y-m-d');
            
            // SECURITY FIX: Add user_id filter to prevent cross-user rollovers
            // Get uncompleted tasks from yesterday only (exclude postponed tasks)
            $whereClause = "scheduled_date = ? AND status IN ('not_started', 'in_progress', 'on_break') AND completed_percentage < 100";
            $params = [$yesterday];
            
            // CRITICAL: Always filter by user_id to prevent data leakage
            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            } else {
                // If no specific user, still require user context for security
                throw new Exception('User ID required for rollover operations');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM daily_tasks WHERE {$whereClause}");
            if (!$stmt->execute($params)) {
                throw new Exception('Failed to fetch uncompleted tasks');
            }
            $uncompletedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $rolledOverCount = 0;
            
            // Wrap rollover operations in transaction for atomicity
            $this->db->beginTransaction();
            
            try {
                foreach ($uncompletedTasks as $task) {
                    // Check if this task is already rolled over to today to prevent duplicates
                    $checkStmt = $this->db->prepare("
                        SELECT COUNT(*) FROM daily_tasks 
                        WHERE user_id = ? AND original_task_id = ? AND scheduled_date = ? AND rollover_source_date = ?
                    ");
                    if (!$checkStmt->execute([$task['user_id'], $task['original_task_id'] ?: $task['task_id'], $today, $yesterday])) {
                        throw new Exception('Failed to check for duplicate rollover');
                    }
                    
                    if ($checkStmt->fetchColumn() == 0) {
                        // Create new rollover entry for today with error handling
                        $stmt = $this->db->prepare("
                            INSERT INTO daily_tasks 
                            (user_id, task_id, original_task_id, title, description, scheduled_date, 
                             planned_start_time, planned_duration, priority, status, 
                             completed_percentage, active_seconds, pause_duration,
                             rollover_source_date, rollover_timestamp, source_field)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'not_started', ?, ?, ?, ?, NOW(), 'rollover')
                        ");
                        
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
                            $task['completed_percentage'],
                            $task['active_seconds'],
                            $task['pause_duration'],
                            $yesterday
                        ]);
                        
                        if (!$result) {
                            throw new Exception("Failed to insert rollover task for user {$task['user_id']}");
                        }
                        
                        if ($stmt->rowCount() > 0) {
                            $newTaskId = $this->db->lastInsertId();
                            
                            // Log rollover action in task history with error handling
                            try {
                                $this->logTaskHistory(
                                    $newTaskId, 
                                    $task['user_id'], 
                                    'rolled_over', 
                                    $yesterday, 
                                    $today, 
                                    "🔄 Rolled over from: {$yesterday}"
                                );
                            } catch (Exception $e) {
                                error_log("Failed to log rollover history: " . $e->getMessage());
                                // Don't fail the entire operation for logging issues
                            }
                            
                            $rolledOverCount++;
                        }
                    }
                }
                
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                error_log("Rollover transaction failed: " . $e->getMessage());
                throw $e;
            }
            
            return $rolledOverCount;
        } catch (Exception $e) {
            error_log("rolloverUncompletedTasks error: " . $e->getMessage());
            return 0;
        }
    }
    
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
    
    public static function runDailyRollover() {
        try {
            $planner = new DailyPlanner();
            
            // Clean up duplicates first
            $cleanedCount = $planner->cleanupDuplicateTasks();
            
            // Then rollover tasks
            $count = $planner->rolloverUncompletedTasks();
            
            // Log rollover completion
            $planner->db->prepare("
                INSERT INTO daily_planner_audit 
                (user_id, action, task_count, details, timestamp)
                VALUES (0, 'daily_rollover', ?, ?, NOW())
            ")->execute([$count, "Automated midnight rollover. Cleaned {$cleanedCount} duplicates."]);
            
            error_log("Daily rollover completed: {$count} tasks rolled over, {$cleanedCount} duplicates cleaned");
            return $count;
        } catch (Exception $e) {
            error_log("Daily rollover failed: " . $e->getMessage());
            return 0;
        }
    }
}
