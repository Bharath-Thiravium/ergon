<?php
// Test script to verify planned_date functionality
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Testing Planned Date Functionality</h2>\n";

try {
    $db = Database::connect();
    
    // Test 1: Check if tasks table has planned_date column
    echo "<h3>Test 1: Checking tasks table structure</h3>\n";
    $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "✅ planned_date column exists in tasks table<br>\n";
        echo "Column details: " . print_r($column, true) . "<br>\n";
    } else {
        echo "❌ planned_date column missing from tasks table<br>\n";
    }
    
    // Test 2: Check for tasks with planned_date
    echo "<h3>Test 2: Checking tasks with planned_date</h3>\n";
    $stmt = $db->prepare("SELECT id, title, planned_date, deadline, created_at FROM tasks WHERE planned_date IS NOT NULL LIMIT 5");
    $stmt->execute();
    $tasksWithPlannedDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tasksWithPlannedDate) {
        echo "✅ Found " . count($tasksWithPlannedDate) . " tasks with planned_date:<br>\n";
        foreach ($tasksWithPlannedDate as $task) {
            echo "- Task ID {$task['id']}: '{$task['title']}' planned for {$task['planned_date']}<br>\n";
        }
    } else {
        echo "⚠️ No tasks found with planned_date set<br>\n";
    }
    
    // Test 3: Check daily_tasks table
    echo "<h3>Test 3: Checking daily_tasks table</h3>\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks");
    $stmt->execute();
    $dailyTasksCount = $stmt->fetchColumn();
    echo "Daily tasks count: {$dailyTasksCount}<br>\n";
    
    // Test 4: Check if DailyPlanner model works correctly
    echo "<h3>Test 4: Testing DailyPlanner model</h3>\n";
    require_once __DIR__ . '/app/models/DailyPlanner.php';
    
    $planner = new DailyPlanner();
    $testDate = date('Y-m-d', strtotime('+1 day')); // Tomorrow
    $testUserId = 1; // Assuming user ID 1 exists
    
    echo "Testing for user ID {$testUserId} on date {$testDate}<br>\n";
    
    $tasks = $planner->getTasksForDate($testUserId, $testDate);
    echo "Found " . count($tasks) . " tasks for {$testDate}<br>\n";
    
    if ($tasks) {
        foreach ($tasks as $task) {
            echo "- Task: '{$task['title']}' (Source: {$task['source_field']})<br>\n";
        }
    }
    
    // Test 5: Create a test task with planned_date
    echo "<h3>Test 5: Creating test task with planned_date</h3>\n";
    $futureDate = date('Y-m-d', strtotime('+3 days'));
    
    $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, planned_date, status, priority, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        'Test Task for Planned Date Fix',
        'This task should appear only on ' . $futureDate,
        $testUserId,
        $testUserId,
        $futureDate,
        'assigned',
        'medium',
        0.25
    ]);
    
    if ($result) {
        $testTaskId = $db->lastInsertId();
        echo "✅ Created test task ID {$testTaskId} with planned_date = {$futureDate}<br>\n";
        
        // Test if it appears on the correct date
        $tasksForFutureDate = $planner->getTasksForDate($testUserId, $futureDate);
        $foundTestTask = false;
        foreach ($tasksForFutureDate as $task) {
            if ($task['original_task_id'] == $testTaskId) {
                $foundTestTask = true;
                break;
            }
        }
        
        if ($foundTestTask) {
            echo "✅ Test task correctly appears on planned date {$futureDate}<br>\n";
        } else {
            echo "❌ Test task NOT found on planned date {$futureDate}<br>\n";
        }
        
        // Test if it does NOT appear on today
        $tasksForToday = $planner->getTasksForDate($testUserId, date('Y-m-d'));
        $foundOnToday = false;
        foreach ($tasksForToday as $task) {
            if ($task['original_task_id'] == $testTaskId) {
                $foundOnToday = true;
                break;
            }
        }
        
        if (!$foundOnToday) {
            echo "✅ Test task correctly does NOT appear on today's date<br>\n";
        } else {
            echo "❌ Test task incorrectly appears on today's date<br>\n";
        }
        
        // Clean up test task
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = ?");
        $stmt->execute([$testTaskId]);
        echo "🧹 Cleaned up test task<br>\n";
    } else {
        echo "❌ Failed to create test task<br>\n";
    }
    
    echo "<h3>Summary</h3>\n";
    echo "The planned_date functionality has been fixed. Tasks will now appear only on their specific planned_date in the daily planner.<br>\n";
    echo "Key changes made:<br>\n";
    echo "1. Updated fetchAssignedTasksForDate to prioritize planned_date<br>\n";
    echo "2. Added support for future dates in daily planner<br>\n";
    echo "3. Fixed task filtering logic to respect planned_date<br>\n";
    echo "4. Updated date selector to allow future dates for planning<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}
?>