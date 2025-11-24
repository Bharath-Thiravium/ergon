<?php
// Simple replacement for getTasksForDate method
public function getTasksForDate($userId, $date) {
    try {
        // Step 1: Fetch assigned tasks first
        $this->fetchAssignedTasksForDate($userId, $date);
        
        // Step 2: Simple query to get tasks
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
?>