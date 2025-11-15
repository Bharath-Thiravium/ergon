<?php

class DailyPlanner {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function getTasksForDate($userId, $date) {
        try {
            // Get from daily_tasks table first
            $stmt = $this->db->prepare("
                SELECT dt.*, t.title as task_title, t.priority as task_priority
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.user_id = ? AND dt.scheduled_date = ?
                ORDER BY dt.priority DESC, dt.planned_start_time ASC
            ");
            $stmt->execute([$userId, $date]);
            $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no daily tasks, get relevant tasks from tasks table
            if (empty($dailyTasks)) {
                return $this->getRelevantTasksForDate($userId, $date);
            }
            
            return $dailyTasks;
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
            $now = date('Y-m-d H:i:s');
            
            $stmt = $this->db->prepare("
                UPDATE daily_tasks 
                SET status = 'in_progress', resume_time = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$now, $taskId, $userId]);
            
            $this->logTimeAction($taskId, $userId, 'resume', $now);
            return true;
        } catch (Exception $e) {
            error_log("DailyPlanner resumeTask error: " . $e->getMessage());
            return false;
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
            $this->updateDailyPerformance($userId, date('Y-m-d'));
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("DailyPlanner completeTask error: " . $e->getMessage());
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
            
            // If no daily tasks stats, get from regular tasks
            if (empty($dailyStats['total_tasks'])) {
                $stmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        0 as postponed_tasks,
                        SUM(sla_hours * 60) as total_planned_minutes,
                        0 as total_active_seconds,
                        AVG(progress) as avg_completion
                    FROM tasks 
                    WHERE assigned_to = ? 
                    AND (
                        DATE(deadline) = ? 
                        OR (DATE(created_at) = ? AND status IN ('assigned', 'in_progress'))
                        OR (status = 'in_progress' AND DATE(updated_at) <= ?)
                    )
                    AND status != 'completed'
                ");
                $stmt->execute([$userId, $date, $date, $date]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $dailyStats;
        } catch (Exception $e) {
            error_log("DailyPlanner getDailyStats error: " . $e->getMessage());
            return [];
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
            $stmt = $this->db->prepare("
                INSERT INTO time_logs (daily_task_id, user_id, action, timestamp, active_duration)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$taskId, $userId, $action, $timestamp, $duration]);
        } catch (Exception $e) {
            error_log("logTimeAction error: " . $e->getMessage());
        }
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
