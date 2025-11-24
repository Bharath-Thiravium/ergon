<?php
/**
 * Test script to verify daily tasks are being fetched correctly for today
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    echo "<h2>Daily Tasks Fetch Test</h2>\n";
    
    $userId = 1; // Test with user ID 1
    $today = date('Y-m-d');
    
    // Test 1: Check tasks table for today
    echo "<h3>Test 1: Tasks in tasks table for today</h3>\n";
    $stmt = $db->prepare("
        SELECT id, title, assigned_to, planned_date, deadline, status, created_at
        FROM tasks 
        WHERE assigned_to = ? 
        AND status NOT IN ('completed', 'cancelled', 'deleted')
        AND (
            DATE(planned_date) = ? OR
            (DATE(deadline) = ? AND (planned_date IS NULL OR planned_date = '')) OR
            (DATE(created_at) = ? AND (planned_date IS NULL OR planned_date = ''))
        )
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $today, $today, $today]);
    $tasksFromTable = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Found " . count($tasksFromTable) . " tasks in tasks table for today:\n";
    foreach ($tasksFromTable as $task) {
        echo "  - ID: {$task['id']}, Title: {$task['title']}, Planned: {$task['planned_date']}, Deadline: {$task['deadline']}\n";
    }
    
    // Test 2: Check daily_tasks table for today
    echo "<h3>Test 2: Tasks in daily_tasks table for today</h3>\n";
    $stmt = $db->prepare("
        SELECT id, task_id, original_task_id, title, scheduled_date, status, created_at
        FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Found " . count($dailyTasks) . " tasks in daily_tasks table for today:\n";
    foreach ($dailyTasks as $task) {
        echo "  - ID: {$task['id']}, Task ID: {$task['task_id']}, Title: {$task['title']}, Status: {$task['status']}\n";
    }
    
    // Test 3: Use DailyPlanner model to fetch tasks
    echo "<h3>Test 3: Using DailyPlanner model</h3>\n";
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    
    echo "✅ DailyPlanner model returned " . count($plannedTasks) . " tasks:\n";
    foreach ($plannedTasks as $task) {
        echo "  - ID: {$task['id']}, Title: {$task['title']}, Status: {$task['status']}\n";
    }
    
    // Test 4: Check if fetchAssignedTasksForDate is working
    echo "<h3>Test 4: Testing fetchAssignedTasksForDate method</h3>\n";
    $addedCount = $planner->fetchAssignedTasksForDate($userId, $today);
    echo "✅ fetchAssignedTasksForDate added {$addedCount} new tasks\n";
    
    // Re-check daily_tasks after fetch
    $stmt->execute([$userId, $today]);
    $dailyTasksAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ After fetch: " . count($dailyTasksAfter) . " tasks in daily_tasks table\n";
    
    // Test 5: Check for duplicates
    echo "<h3>Test 5: Checking for duplicates</h3>\n";
    $stmt = $db->prepare("
        SELECT original_task_id, COUNT(*) as count
        FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? AND original_task_id IS NOT NULL
        GROUP BY original_task_id
        HAVING COUNT(*) > 1
    ");
    $stmt->execute([$userId, $today]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "✅ No duplicates found\n";
    } else {
        echo "⚠️  Found " . count($duplicates) . " duplicate task groups:\n";
        foreach ($duplicates as $dup) {
            echo "  - Task ID {$dup['original_task_id']} appears {$dup['count']} times\n";
        }
    }
    
    echo "<h3>Summary</h3>\n";
    if (count($plannedTasks) === 1 && count($tasksFromTable) > 1) {
        echo "❌ ISSUE CONFIRMED: Multiple tasks exist but only 1 is being returned by DailyPlanner\n";
        echo "🔧 This indicates a problem in the DailyPlanner model's task fetching logic\n";
    } elseif (count($plannedTasks) > 1) {
        echo "✅ WORKING: Multiple tasks are being returned correctly\n";
    } else {
        echo "ℹ️  Only 1 task found - this may be normal if only 1 task exists for today\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>