<?php
// Cleanup script for postponed tasks
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Cleanup Postponed Tasks</h2>";
    
    // Reset postponed_to_date for tasks that might be causing issues
    $stmt = $db->prepare("
        UPDATE daily_tasks 
        SET postponed_to_date = NULL 
        WHERE status != 'postponed' AND postponed_to_date IS NOT NULL
    ");
    $result = $stmt->execute();
    $affectedRows = $stmt->rowCount();
    
    echo "<p>Reset postponed_to_date for {$affectedRows} non-postponed tasks.</p>";
    
    // Clean up orphaned postponed tasks (tasks marked as postponed but no corresponding future task)
    $stmt = $db->prepare("
        SELECT id, title, scheduled_date, postponed_to_date 
        FROM daily_tasks 
        WHERE status = 'postponed' AND postponed_to_date IS NOT NULL
    ");
    $stmt->execute();
    $postponedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cleanedCount = 0;
    foreach ($postponedTasks as $task) {
        // Check if there's a corresponding task on the postponed date
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM daily_tasks 
            WHERE scheduled_date = ? AND postponed_from_date = ? AND original_task_id = ?
        ");
        $checkStmt->execute([$task['postponed_to_date'], $task['scheduled_date'], $task['id']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            // No corresponding future task found, reset this task
            $resetStmt = $db->prepare("
                UPDATE daily_tasks 
                SET status = 'not_started', postponed_to_date = NULL 
                WHERE id = ?
            ");
            $resetStmt->execute([$task['id']]);
            $cleanedCount++;
            echo "<p>Reset orphaned postponed task: {$task['title']} (ID: {$task['id']})</p>";
        }
    }
    
    echo "<p>Cleaned up {$cleanedCount} orphaned postponed tasks.</p>";
    echo "<p><strong>Cleanup complete! You can now try postponing tasks again.</strong></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>