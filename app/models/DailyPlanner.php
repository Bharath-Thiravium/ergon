<?php

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
            
            // REMOVED: The auto-rollover logic is now handled exclusively and correctly in the UnifiedWorkflowController.
            
            // 🖥️ Step 3: Display Tasks in UI
            /* REFACTORED to use a single dynamic query builder
             if ($isCurrentDate) {
                // Logic for Today's View:
                // Show all tasks with scheduled_date = today
                // Include rolled-over tasks (with rollover_source_date IS NOT NULL)
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
                        END as task_indicator,
                        'current_day' as view_type
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? AND dt.scheduled_date = ?
                    ORDER BY 
                        CASE WHEN dt.rollover_source_date IS NOT NULL THEN 1 ELSE 0 END,
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
                            WHEN dt.source_field = 'postponed' THEN '🔄 Postponed to this date'
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
                // Logic for Past Dates:
                // Show only tasks with scheduled_date = [past_date]
                // Tasks completed on [past_date] (based on updated_at)
                // Exclude rolled-over tasks from other dates
                $stmt = $this->db->prepare("
                    SELECT 
                        dt.id, dt.title, dt.description, dt.priority, dt.status,
                        dt.completed_percentage, dt.start_time, dt.active_seconds,
                        dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                        dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                        dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                        COALESCE(t.sla_hours, 0.25) as sla_hours,
                        CASE 
                            WHEN dt.status = 'completed' AND DATE(dt.updated_at) = ? THEN '✅ Completed on this date'
                            WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                            ELSE '📜 Historical View Only'
                        END as task_indicator,
                        'historical' as view_type
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.user_id = ? 
                    AND (
                        dt.scheduled_date = ? 
                        OR (dt.status = 'completed' AND DATE(dt.updated_at) = ?)
                    )
                    AND (dt.rollover_source_date IS NULL OR dt.rollover_source_date = ?)
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
                $stmt->execute([$date, $userId, $date, $date, $date]);
            } */

            // ✅ REFACTORED LOGIC: Use a single base query and build conditions dynamically.
            $baseQuery = "
                SELECT 
                    dt.id, dt.title, dt.description, dt.priority, dt.status,
                    dt.completed_percentage, dt.start_time, dt.active_seconds,
                    dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                    dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                    dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                    COALESCE(t.sla_hours, 0.25) as sla_hours,
                    %s AS task_indicator,
                    '%s' as view_type
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.original_task_id = t.id
            ";

            $whereClause = "WHERE dt.user_id = ?";
            $orderByClause = "";
            $params = [$userId];

            if ($isCurrentDate) {
                $indicatorCase = "CASE 
                    WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
                    WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                    WHEN t.assigned_by != t.assigned_to THEN '👥 From Others'
                    ELSE '👤 Self-Assigned'
                END";
                $viewType = 'current_day';
                $whereClause .= " AND dt.scheduled_date = ?";
                $params[] = $date;
                $orderByClause = "ORDER BY CASE WHEN dt.rollover_source_date IS NOT NULL THEN 0 ELSE 1 END, CASE dt.status WHEN 'in_progress' THEN 1 WHEN 'on_break' THEN 2 WHEN 'not_started' THEN 3 ELSE 4 END, CASE dt.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END";
            } elseif ($isFutureDate) {
                $indicatorCase = "CASE 
                    WHEN dt.source_field = 'planned_date' THEN '📅 Planned for this date'
                    WHEN dt.source_field = 'deadline' THEN '⏰ Deadline on this date'
                    WHEN dt.postponed_to_date = ? THEN '🔄 Postponed to this date'
                    ELSE '📋 Planning Mode'
                END";
                $viewType = 'planning';
                $whereClause .= " AND dt.scheduled_date = ?";
                array_unshift($params, $date); // Add to the beginning for the CASE statement
                $params[] = $date;
                $orderByClause = "ORDER BY CASE dt.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, dt.created_at ASC";
            } else { // isPastDate
                $indicatorCase = "CASE 
                    WHEN dt.status = 'completed' AND DATE(dt.updated_at) = ? THEN '✅ Completed on this date'
                    WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                    ELSE '📜 Historical View Only'
                END";
                $viewType = 'historical';
                $whereClause .= " AND (dt.scheduled_date = ? OR (dt.status = 'completed' AND DATE(dt.updated_at) = ?)) AND (dt.rollover_source_date IS NULL OR dt.rollover_source_date = ?)";
                array_unshift($params, $date); // Add to the beginning for the CASE statement
                $params = array_merge($params, [$date, $date, $date]);
                $orderByClause = "ORDER BY CASE dt.status WHEN 'completed' THEN 1 ELSE 2 END, CASE dt.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, dt.created_at ASC";
            }

            $finalQuery = sprintf($baseQuery, $indicatorCase, $viewType) . $whereClause . $orderByClause;
            $stmt = $this->db->prepare($finalQuery);
            $stmt->execute($params);
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
                $stmt = $this->db->prepare("
                    SELECT 
                        t.id, t.title, t.description, t.priority, t.status,
                        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                        'planned_date' as source_field
                    FROM tasks t
                    WHERE t.assigned_to = ? 
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND t.planned_date = ?
                    AND t.planned_date IS NOT NULL 
                    AND t.planned_date != '' 
                    AND t.planned_date != '0000-00-00'
                ");
                $stmt->execute([$userId, $date]);
            } else {
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
                    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
                    AND (
                        DATE(t.planned_date) = ? OR
                        (DATE(t.deadline) = ? AND t.planned_date IS NULL) OR
                        (? = CURDATE() AND ? = CURDATE() AND DATE(t.created_at) = ? AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00') AND t.deadline IS NULL)
                    )
                ");
                $stmt->execute([$date, $date, $date, $date, $userId, $date, $date, $date]);
            }
            
            $relevantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $addedCount = 0;
            
            foreach ($relevantTasks as $task) {
                // Check for exact duplicates only - must match BOTH task_id AND original_task_id
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM daily_tasks 
                    WHERE user_id = ? AND scheduled_date = ? 
                    AND task_id = ? AND original_task_id = ?
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
            $this->addPostponeColumns();
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
            
            // Check if already postponed to the same date
            if ($currentTask['postponed_to_date'] === $newDate) {
                throw new Exception('This task is already postponed to this date.');
            }
            
            // Check if task already exists on target date
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM daily_tasks 
                WHERE original_task_id = ? AND scheduled_date = ? AND user_id = ?
            ");
            $stmt->execute([$currentTask['original_task_id'] ?: $currentTask['task_id'], $newDate, $userId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('A task with this content already exists on the target date.');
            }
            
            $this->db->beginTransaction();
            
            // Update original task as postponed
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'postponed', postponed_from_date = ?, postponed_to_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$currentTask['scheduled_date'], $newDate, $taskId]);
            
            // Create new entry for the future date with preserved data
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
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DailyPlanner postponeTask error: " . $e->getMessage());
            throw $e;
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
        // ✅ CONSOLIDATED: Log time actions to the main history table for a single source of truth.
        // The old time_logs table is now redundant.
        $notes = "Action: {$action} at {$timestamp}. Duration: {$duration}s.";
        $this->logTaskHistory($taskId, $userId, "time_{$action}", $duration, null, $notes);
    }
    
    // REMOVED: ensureTimeLogsTable() is no longer needed as the table is deprecated.
    
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
