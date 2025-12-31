<?php

class SLACalculatorService {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
    }
    
    public function calculateDailySLA($userId, $date) {
        // Get today's tasks with SLA data
        $stmt = $this->db->prepare("
            SELECT dt.*, COALESCE(t.sla_hours, 1.0) as sla_hours
            FROM daily_tasks dt 
            LEFT JOIN tasks t ON dt.task_id = t.id
            WHERE dt.user_id = ? AND dt.scheduled_date = ?
        ");
        $stmt->execute([$userId, $date]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalSlaSeconds = 0;
        $totalActiveSeconds = 0;
        $totalPauseSeconds = 0;
        $completedTasks = 0;
        $now = time();
        
        foreach ($tasks as $task) {
            // SLA calculation
            $slaSeconds = max(900, floatval($task['sla_hours']) * 3600);
            $totalSlaSeconds += $slaSeconds;
            
            // Active time calculation (including current session)
            $currentActiveTime = 0;
            if ($task['status'] === 'in_progress' && $task['start_time']) {
                $lastActiveTime = $task['resume_time'] ?: $task['start_time'];
                $currentActiveTime = $now - strtotime($lastActiveTime);
            }
            
            $taskActiveTime = intval($task['active_seconds'] ?? 0) + $currentActiveTime;
            $totalActiveSeconds += $taskActiveTime;
            
            // Pause duration
            $totalPauseSeconds += intval($task['total_pause_duration'] ?? 0);
            
            // Count completed tasks
            if ($task['status'] === 'completed') {
                $completedTasks++;
            }
        }
        
        $totalRemainingSeconds = max(0, $totalSlaSeconds - $totalActiveSeconds);
        $completionRate = count($tasks) > 0 ? ($completedTasks / count($tasks)) * 100 : 0;
        
        // Update summary table
        $this->updateSLASummary($userId, $date, [
            'total_sla_seconds' => $totalSlaSeconds,
            'total_active_seconds' => $totalActiveSeconds,
            'total_pause_seconds' => $totalPauseSeconds,
            'total_tasks' => count($tasks),
            'completed_tasks' => $completedTasks
        ]);
        
        return [
            'sla_total_seconds' => $totalSlaSeconds,
            'active_seconds' => $totalActiveSeconds,
            'remaining_seconds' => $totalRemainingSeconds,
            'pause_seconds' => $totalPauseSeconds,
            'completion_rate' => round($completionRate, 1),
            'task_count' => count($tasks),
            'completed_tasks' => $completedTasks
        ];
    }
    
    private function updateSLASummary($userId, $date, $data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO daily_sla_summary 
                (user_id, date, total_sla_seconds, total_active_seconds, total_pause_seconds, total_tasks, completed_tasks)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                total_sla_seconds = VALUES(total_sla_seconds),
                total_active_seconds = VALUES(total_active_seconds),
                total_pause_seconds = VALUES(total_pause_seconds),
                total_tasks = VALUES(total_tasks),
                completed_tasks = VALUES(completed_tasks)
            ");
            
            $stmt->execute([
                $userId, $date,
                $data['total_sla_seconds'],
                $data['total_active_seconds'], 
                $data['total_pause_seconds'],
                $data['total_tasks'],
                $data['completed_tasks']
            ]);
        } catch (Exception $e) {
            error_log('SLA Summary update error: ' . $e->getMessage());
        }
    }
}
?>
