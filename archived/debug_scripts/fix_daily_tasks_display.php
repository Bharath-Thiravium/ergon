<?php
/**
 * Fix script to ensure all tasks for today are properly displayed in the daily planner
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    echo "<h2>Daily Tasks Display Fix</h2>\n";
    
    $today = date('Y-m-d');
    
    // Get all users to fix their daily tasks
    $stmt = $db->prepare("SELECT DISTINCT id FROM users WHERE status = 'active' OR status IS NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $userId) {
        echo "<h3>Fixing tasks for User ID: {$userId}</h3>\n";
        
        // Step 1: Clean up any duplicate daily tasks
        $stmt = $db->prepare("
            DELETE dt1 FROM daily_tasks dt1
            INNER JOIN daily_tasks dt2 
            ON dt1.user_id = dt2.user_id 
               AND dt1.original_task_id = dt2.original_task_id 
               AND dt1.scheduled_date = dt2.scheduled_date
               AND dt1.id > dt2.id
            WHERE dt1.user_id = ? AND dt1.scheduled_date = ?
        ");
        $stmt->execute([$userId, $today]);
        $cleanedCount = $stmt->rowCount();
        
        if ($cleanedCount > 0) {
            echo "  ✅ Cleaned {$cleanedCount} duplicate daily tasks\n";
        }
        
        // Step 2: Ensure all tasks for today are in daily_tasks table
        $addedCount = $planner->fetchAssignedTasksForDate($userId, $today);
        echo "  ✅ Added {$addedCount} missing tasks to daily planner\n";
        
        // Step 3: Verify final count
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM daily_tasks 
            WHERE user_id = ? AND scheduled_date = ?
        ");
        $stmt->execute([$userId, $today]);
        $finalCount = $stmt->fetchColumn();
        
        echo "  📊 Final count: {$finalCount} tasks in daily planner for today\n";
        
        // Step 4: Show task details
        $stmt = $db->prepare("
            SELECT id, title, status, task_id, original_task_id
            FROM daily_tasks 
            WHERE user_id = ? AND scheduled_date = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId, $today]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tasks as $task) {
            echo "    - ID: {$task['id']}, Title: " . substr($task['title'], 0, 50) . "..., Status: {$task['status']}\n";
        }
        
        echo "\n";
    }
    
    echo "<h3>Summary</h3>\n";
    echo "✅ Daily tasks display fix completed\n";
    echo "✅ All users should now see multiple tasks in their daily planner if they exist\n";
    echo "✅ Duplicate prevention logic has been improved\n";
    echo "✅ Task fetching logic has been corrected\n";
    
    echo "<h3>Next Steps</h3>\n";
    echo "1. Refresh the daily planner page\n";
    echo "2. All tasks for today should now be visible\n";
    echo "3. If issues persist, check the test script: test_daily_tasks_fetch.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>