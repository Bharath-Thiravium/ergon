<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h1>Cleanup Old Daily Tasks</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}</style>";
    
    // Remove daily_tasks entries where the source_field was 'created_date' 
    // and the task's actual planned_date doesn't match the scheduled_date
    $stmt = $db->prepare("
        DELETE dt FROM daily_tasks dt
        JOIN tasks t ON dt.task_id = t.id
        WHERE dt.source_field = 'created_date'
        AND t.planned_date IS NOT NULL
        AND t.planned_date != dt.scheduled_date
    ");
    $result = $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo "<p class='info'>Removed {$deletedCount} daily_tasks entries that were incorrectly created based on creation date</p>";
    
    // Also remove entries where source_field is 'created_date' and the task has a planned_date
    $stmt = $db->prepare("
        DELETE dt FROM daily_tasks dt
        JOIN tasks t ON dt.task_id = t.id
        WHERE dt.source_field = 'created_date'
        AND t.planned_date IS NOT NULL
    ");
    $result = $stmt->execute();
    $deletedCount2 = $stmt->rowCount();
    
    echo "<p class='info'>Removed {$deletedCount2} additional daily_tasks entries with created_date source but having planned_date</p>";
    
    echo "<div style='padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin-top:20px;'>";
    echo "<h3 style='color:#155724;margin:0 0 10px 0;'>✅ Cleanup Complete!</h3>";
    echo "<p style='margin:0;'>Removed " . ($deletedCount + $deletedCount2) . " incorrect daily_tasks entries.</p>";
    echo "<p style='margin:10px 0 0 0;'>Planner now shows tasks ONLY based on planned_date.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>