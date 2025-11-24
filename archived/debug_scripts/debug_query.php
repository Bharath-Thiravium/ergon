<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $userId = 1;
    $date = date('Y-m-d');
    
    echo "<h1>Debug Query Issue</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}</style>";
    
    // Test the exact query from getTasksForDate for current date
    $indicatorCase = "CASE 
        WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
        WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
        WHEN t.assigned_by != t.assigned_to THEN '👥 From Others'
        ELSE '👤 Self-Assigned'
    END";
    
    $query = "
        SELECT 
            dt.id, dt.title, dt.description, dt.priority, dt.status,
            dt.completed_percentage, dt.start_time, dt.active_seconds,
            dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
            dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
            dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
            COALESCE(t.sla_hours, 0.25) as sla_hours,
            {$indicatorCase} AS task_indicator,
            'current_day' as view_type
        FROM daily_tasks dt
        LEFT JOIN tasks t ON dt.original_task_id = t.id
        WHERE dt.user_id = ? AND dt.scheduled_date = ?
        ORDER BY CASE WHEN dt.rollover_source_date IS NOT NULL THEN 0 ELSE 1 END, 
                 CASE dt.status WHEN 'in_progress' THEN 1 WHEN 'on_break' THEN 2 WHEN 'not_started' THEN 3 ELSE 4 END, 
                 CASE dt.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
    ";
    
    echo "<h2>Query:</h2>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    echo "<p>Parameters: userId={$userId}, date={$date}</p>";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Results: " . count($tasks) . " tasks</h2>";
    
    if (!empty($tasks)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($tasks[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        foreach ($tasks as $task) {
            echo "<tr>";
            foreach ($task as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No tasks found. Let's check what's in daily_tasks:</p>";
        
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $date]);
        $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Daily tasks count: " . count($dailyTasks) . "</p>";
        
        if (!empty($dailyTasks)) {
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($dailyTasks[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            
            foreach ($dailyTasks as $task) {
                echo "<tr>";
                foreach ($task as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>