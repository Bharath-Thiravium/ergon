<?php
// Debug script to understand the planned_date issue
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

echo "<h2>Debug Planned Date Issue</h2>\n";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $testUserId = 1;
    $testDate = '2025-11-25';
    
    echo "Current date: " . date('Y-m-d') . "<br>\n";
    echo "Test date: {$testDate}<br>\n";
    echo "Is future date: " . ($testDate > date('Y-m-d') ? 'YES' : 'NO') . "<br>\n";
    
    // Check if there are any tasks with planned_date = 2025-11-25
    $stmt = $db->prepare("SELECT id, title, planned_date, status FROM tasks WHERE assigned_to = ? AND DATE(planned_date) = ?");
    $stmt->execute([$testUserId, $testDate]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tasks with planned_date = {$testDate}:</h3>\n";
    if (empty($tasks)) {
        echo "No tasks found with planned_date = {$testDate}<br>\n";
    } else {
        foreach ($tasks as $task) {
            echo "- Task ID {$task['id']}: '{$task['title']}' (planned_date: {$task['planned_date']}, status: {$task['status']})<br>\n";
        }
    }
    
    // Check daily_tasks table
    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$testUserId, $testDate]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Daily tasks for {$testDate}:</h3>\n";
    if (empty($dailyTasks)) {
        echo "No daily tasks found for {$testDate}<br>\n";
    } else {
        foreach ($dailyTasks as $task) {
            echo "- Daily Task ID {$task['id']}: '{$task['title']}' (original_task_id: {$task['original_task_id']}, source: {$task['source_field']})<br>\n";
        }
    }
    
    // Test fetchAssignedTasksForDate directly
    echo "<h3>Testing fetchAssignedTasksForDate:</h3>\n";
    $addedCount = $planner->fetchAssignedTasksForDate($testUserId, $testDate);
    echo "Added {$addedCount} tasks<br>\n";
    
    // Check daily_tasks again after fetch
    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$testUserId, $testDate]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Daily tasks after fetch:</h3>\n";
    if (empty($dailyTasks)) {
        echo "Still no daily tasks found for {$testDate}<br>\n";
    } else {
        foreach ($dailyTasks as $task) {
            echo "- Daily Task ID {$task['id']}: '{$task['title']}' (original_task_id: {$task['original_task_id']}, source: {$task['source_field']})<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}
?>