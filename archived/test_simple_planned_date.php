<?php
// Simple test for planned_date functionality
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

echo "<h2>Simple Planned Date Test</h2>\n";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $testUserId = 1;
    $futureDate = '2025-11-25';  // Use fixed future date for consistency
    
    echo "Testing with User ID: {$testUserId}, Date: {$futureDate}<br>\n";
    
    // Create a test task with planned_date
    $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, planned_date, status, priority, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        'Simple Test Task',
        'This should appear on ' . $futureDate,
        $testUserId,
        $testUserId,
        $futureDate,
        'assigned'
        'medium',
        0.25
    ]);
    
    if (!$result) {
        echo "❌ Failed to create test task<br>\n";
        exit;
    }
    
    $testTaskId = $db->lastInsertId();
    echo "✅ Created test task ID {$testTaskId}<br>\n";
    
    // Clear any existing daily_tasks for this test
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = ?");
    $stmt->execute([$testTaskId]);
    
    // Test the planner
    echo "<h3>Testing DailyPlanner</h3>\n";
    $tasks = $planner->getTasksForDate($testUserId, $futureDate);
    
    echo "Found " . count($tasks) . " tasks for {$futureDate}<br>\n";
    
    $foundTestTask = false;
    foreach ($tasks as $task) {
        echo "- Task: '{$task['title']}' (ID: {$task['id']}, Original: {$task['original_task_id']}, Source: {$task['source_field']})<br>\n";
        if ($task['original_task_id'] == $testTaskId) {
            $foundTestTask = true;
        }
    }
    
    if ($foundTestTask) {
        echo "✅ SUCCESS: Test task found on planned date!<br>\n";
    } else {
        echo "❌ FAILED: Test task not found on planned date<br>\n";
        
        // Debug: Check if the original task exists and has correct data
        $stmt = $db->prepare("SELECT id, title, planned_date, status, assigned_to FROM tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $originalTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($originalTask) {
            echo "Original task exists: ID {$originalTask['id']}, planned_date: {$originalTask['planned_date']}, status: {$originalTask['status']}, assigned_to: {$originalTask['assigned_to']}<br>\n";
        } else {
            echo "Original task not found in tasks table!<br>\n";
        }
        
        // Debug: Check if task was inserted into daily_tasks
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE original_task_id = ?");
        $stmt->execute([$testTaskId]);
        $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dailyTask) {
            echo "Daily task exists: ID {$dailyTask['id']}, Date: {$dailyTask['scheduled_date']}<br>\n";
        } else {
            echo "No daily task found for original_task_id {$testTaskId}<br>\n";
            
            // Test the fetchAssignedTasksForDate method directly
            echo "Testing fetchAssignedTasksForDate directly...<br>\n";
            $addedCount = $planner->fetchAssignedTasksForDate($testUserId, $futureDate);
            echo "fetchAssignedTasksForDate returned: {$addedCount}<br>\n";
        }
    }
    
    // Test that it doesn't appear on today
    $tasksToday = $planner->getTasksForDate($testUserId, date('Y-m-d'));
    $foundOnToday = false;
    foreach ($tasksToday as $task) {
        if ($task['original_task_id'] == $testTaskId) {
            $foundOnToday = true;
            break;
        }
    }
    
    if (!$foundOnToday) {
        echo "✅ SUCCESS: Test task correctly does NOT appear on today<br>\n";
    } else {
        echo "❌ FAILED: Test task incorrectly appears on today<br>\n";
    }
    
    // Cleanup
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = ?");
    $stmt->execute([$testTaskId]);
    
    echo "<br>🧹 Test completed and cleaned up<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}
?>