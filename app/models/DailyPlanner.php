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
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    scheduled_date DATE NOT NULL,
                    planned_start_time TIME NULL,
                    planned_duration INT DEFAULT 60,
                    priority ENUM('low','medium','high') DEFAULT 'medium',
                    status ENUM('not_started','in_progress','paused','completed','postponed','cancelled') DEFAULT 'not_started',
                    completed_percentage INT DEFAULT 0,
                    start_time TIMESTAMP NULL,
                    pause_time TIMESTAMP NULL,
                    resume_time TIMESTAMP NULL,
                    completion_time TIMESTAMP NULL,
                    active_seconds INT DEFAULT 0,
                    postponed_from_date DATE NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_date (user_id, scheduled_date),
                    INDEX idx_task_id (task_id),
                    INDEX idx_status (status)
                )
            ");
        } catch (Exception $e) {
            error_log('ensureDailyTasksTable error: ' . $e->getMessage());
        }
    }
    
    public function getTasksForDate($userId, $date) {
        try {
            // Get tasks from daily_tasks table with proper SLA data
            $stmt = $this->db->prepare("
                SELECT 
                    dt.id, dt.title, dt.description, dt.priority, dt.status,
                    dt.completed_percentage, dt.start_time, dt.active_seconds,
                    dt.planned_duration, dt.task_id,
                    COALESCE(t.sla_hours, 1) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.user_id = ? AND dt.scheduled_date = ?
                ORDER BY 
                    CASE dt.status 
                        WHEN 'in_progress' THEN 1 
                        WHEN 'on_break' THEN 2 
                        WHEN 'not_started' THEN 3 
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
    
    private function getRelevantTasksForDate($userId, $date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.id, t.title, t.description, t.priority, t.status,
                    t.deadline, t.estimated_duration, t.sla_hours,
                    'not_started' as completion_status, 0 as active_seconds,
                    0 as completed_percentage, NULL as start_time,
                    NULL as planned_start_time, NULL as planned_duration
                FROM tasks t
                WHERE t.assigned_to = ? 
                AND (
                    DATE(t.deadline) = ? 
                    OR (DATE(t.created_at) = ? AND t.status IN ('assigned', 'in_progress'))
                    OR (t.status = 'in_progress' AND DATE(t.updated_at) <= ?)
                )
                AND t.status != 'completed'
                ORDER BY 
                    CASE t.priority 
                        WHEN 'high' THEN 3 
                        WHEN 'medium' THEN 2 
                        WHEN 'low' THEN 1 
                        ELSE 0 
                    END DESC, 
                    t.created_at DESC
                LIMIT 15
            ");
            $stmt->execute([$userId, $date, $date, $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DailyPlanner getRelevantTasksForDate error: " . $e->getMessage());
            return [];
        }
    }
    
    public function startTask($taskId, $userId) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            
            // Update daily_tasks
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', start_time = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$now, $taskId, $userId]);
            
            // Log time action
            $this->logTimeAction($taskId, $userId, 'start', $now);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("DailyPlanner startTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function pauseTask($taskId, $userId) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            
            // Calculate active time since start/resume
            $activeTime = $this->calculateActiveTime($taskId);
            
            // Update daily_tasks
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'paused', pause_time = ?, 
                    active_seconds = active_seconds + ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$now, $activeTime, $taskId, $userId]);
            
            // Log time action
            $this->logTimeAction($taskId, $userId, 'pause', $now, $activeTime);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
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
            
            // Check if task exists and belongs to user
            $stmt = $this->db->prepare("
                SELECT id, status, title 
                FROM daily_tasks 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$taskId, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                throw new Exception("Task not found or access denied");
            }
            
            // Check if task can be resumed
            if (!in_array($task['status'], ['paused', 'on_break'])) {
                throw new Exception("Task cannot be resumed. Current status: " . $task['status']);
            }
            
            $now = date('Y-m-d H:i:s');
            
            // Update task status
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', resume_time = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$now, $taskId, $userId]);
            
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
                // Log error but don't fail the resume operation
                error_log("Failed to log time action: " . $e->getMessage());
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
            $this->db->rollback();
            error_log("DailyPlanner completeTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTaskProgress($taskId, $userId, $progress, $status, $reason = '') {
        try {
            $this->db->beginTransaction();
            
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
                $stmt->execute([$newStatus, $progress, $activeTime, $taskId, $userId]);
                
                $this->logTimeAction($taskId, $userId, 'complete', date('Y-m-d H:i:s'), $activeTime);
            } else {
                $newStatus = $progress > 0 ? 'in_progress' : 'assigned';
                
                // Update progress only
                $stmt = $this->db->prepare("
                    UPDATE daily_tasks 
                    SET status = ?, completed_percentage = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$newStatus, $progress, $taskId, $userId]);
            }
            
            // Update linked task if exists
            $stmt = $this->db->prepare("
                UPDATE tasks t 
                JOIN daily_tasks dt ON t.id = dt.task_id
                SET t.status = ?, t.progress = ?
                WHERE dt.id = ?
            ");
            $stmt->execute([$newStatus, $progress, $taskId]);
            
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
            $this->db->rollback();
            error_log("DailyPlanner updateTaskProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    public function postponeTask($taskId, $userId, $newDate) {
        try {
            $this->db->beginTransaction();
            
            $now = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');
            
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'postponed', scheduled_date = ?, postponed_from_date = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$newDate, $currentDate, $taskId, $userId]);
            
            $this->logTimeAction($taskId, $userId, 'postpone', $now);
            $this->updateDailyPerformance($userId, $currentDate);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("DailyPlanner postponeTask error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDailyStats($userId, $date) {
        try {
            // First try to get stats from daily_tasks
            try {
                $stmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN status = 'postponed' THEN 1 ELSE 0 END) as postponed_tasks,
                        SUM(planned_duration) as total_planned_minutes,
                        SUM(active_seconds) as total_active_seconds,
                        AVG(completed_percentage) as avg_completion
                    FROM daily_tasks 
                    WHERE user_id = ? AND scheduled_date = ?
                ");
                $stmt->execute([$userId, $date]);
                $dailyStats = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Daily stats complex query failed, using fallback: ' . $e->getMessage());
                $stmt = $this->db->prepare("SELECT COUNT(*) as total_tasks FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                $stmt->execute([$userId, $date]);
                $dailyStats = ['total_tasks' => $stmt->fetchColumn(), 'completed_tasks' => 0, 'in_progress_tasks' => 0, 'postponed_tasks' => 0, 'total_planned_minutes' => 0, 'total_active_seconds' => 0, 'avg_completion' => 0];
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
            $stmt->execute([$taskId, $userId, $action, $timestamp, $duration]);
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
            default => ucfirst(str_replace('_', ' ', $action))
        };
    }
    
    private function extractProgressFromValue($value) {
        if (strpos($value, '%') !== false) {
            return intval(str_replace('%', '', $value));
        }
        return 0;
    }
    
    private function updateDailyPerformance($userId, $date) {
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
}
