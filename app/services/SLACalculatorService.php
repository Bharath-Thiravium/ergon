<?php

class SLACalculatorService {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
    }
    
    public function calculateDailySLA($userId, $date) {
        $stmt = $this->db->prepare("
            SELECT dt.status, dt.start_ts_ms, dt.paused_accum_ms,
                   COALESCE(dt.sla_duration_seconds,
                            COALESCE(t.sla_hours, dt.sla_hours, 0.25) * 3600) AS sla_duration_seconds
            FROM daily_tasks dt
            LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
            WHERE dt.user_id = ? AND dt.scheduled_date = ?
        ");
        $stmt->execute([$userId, $date]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSlaSeconds    = 0;
        $totalActiveSeconds = 0;
        $totalPauseSeconds  = 0;
        $completedTasks     = 0;
        $nowMs              = (int)(microtime(true) * 1000);

        foreach ($tasks as $task) {
            $slaSec  = max(60, (int)round((float)$task['sla_duration_seconds']));
            $accumMs = (int)($task['paused_accum_ms'] ?? 0);
            $startMs = (int)($task['start_ts_ms'] ?? 0);

            if (in_array($task['status'], ['in_progress', 'overdue'], true) && $startMs > 0) {
                $workingMs = $accumMs + max(0, $nowMs - $startMs);
            } else {
                $workingMs = $accumMs;
            }

            $totalSlaSeconds    += $slaSec;
            $totalActiveSeconds += (int)round($workingMs / 1000);

            if ($task['status'] === 'completed') {
                $completedTasks++;
            }
        }
        
        $totalRemainingSeconds = max(0, $totalSlaSeconds - $totalActiveSeconds);
        $completionRate = count($tasks) > 0 ? ($completedTasks / count($tasks)) * 100 : 0;

        $this->updateSLASummary($userId, $date, [
            'total_sla_seconds'    => $totalSlaSeconds,
            'total_active_seconds' => $totalActiveSeconds,
            'total_pause_seconds'  => $totalPauseSeconds,
            'total_tasks'          => count($tasks),
            'completed_tasks'      => $completedTasks,
        ]);

        return [
            'sla_total_seconds' => $totalSlaSeconds,
            'active_seconds'    => $totalActiveSeconds,
            'remaining_seconds' => $totalRemainingSeconds,
            'pause_seconds'     => $totalPauseSeconds,
            'completion_rate'   => round($completionRate, 1),
            'task_count'        => count($tasks),
            'completed_tasks'   => $completedTasks,
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
