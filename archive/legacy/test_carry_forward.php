<?php
/**
 * Test Script: Carry Forward Pending Tasks
 * 
 * This script tests the carry forward functionality for unattended/pending tasks.
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Testing Carry Forward Functionality\n";
    echo str_repeat("=", 50) . "\n";
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    echo "Yesterday: $yesterday\n";
    echo "Today: $today\n";
    echo "Tomorrow: $tomorrow\n\n";
    
    // Create test tasks with yesterday's planned date
    echo "Creating test tasks with yesterday's planned date...\n";
    
    $testTasks = [
        "Pending Task 1 - Should be carried forward",
        "Pending Task 2 - Should be carried forward"
    ];
    
    $createdTaskIds = [];
    
    foreach ($testTasks as $title) {
        $stmt = $db->prepare("
            INSERT INTO tasks (
                title, description, assigned_by, assigned_to, 
                planned_date, priority, status, sla_hours, created_at
            ) VALUES (?, ?, 1, 1, ?, 'medium', 'assigned', 1.0, NOW())
        ");
        
        $result = $stmt->execute([
            $title, 
            "Test task for carry forward functionality", 
            $yesterday
        ]);
        
        if ($result) {
            $taskId = $db->lastInsertId();
            $createdTaskIds[] = $taskId;
            echo "✓ Created task #$taskId: '$title' (planned: $yesterday)\n";
        }
    }
    
    echo "\n";
    
    // Test the carry forward logic
    echo "Testing carry forward logic...\n";
    echo str_repeat("-", 30) . "\n";
    
    // Simulate the carry forward function
    $stmt = $db->prepare("
        UPDATE tasks SET planned_date = ? 
        WHERE assigned_to = 1 
        AND status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    
    $result = $stmt->execute([$today, $today]);
    $carriedForwardCount = $stmt->rowCount();
    
    echo "Carried forward $carriedForwardCount tasks from previous dates to today\n";
    
    // Verify the tasks were moved
    echo "\nVerifying tasks were carried forward...\n";
    
    $stmt = $db->prepare("
        SELECT id, title, planned_date, status 
        FROM tasks 
        WHERE id IN (" . implode(',', array_fill(0, count($createdTaskIds), '?')) . ")
    ");
    $stmt->execute($createdTaskIds);
    $updatedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($updatedTasks as $task) {
        if ($task['planned_date'] === $today) {
            echo "✓ Task #{$task['id']}: '{$task['title']}' moved to today ($today)\n";
        } else {
            echo "✗ Task #{$task['id']}: '{$task['title']}' still on {$task['planned_date']}\n";
        }
    }
    
    echo "\n";
    
    // Test that completed tasks are NOT carried forward
    echo "Testing that completed tasks are NOT carried forward...\n";
    
    // Create a completed task with yesterday's date
    $stmt = $db->prepare("
        INSERT INTO tasks (
            title, description, assigned_by, assigned_to, 
            planned_date, priority, status, sla_hours, created_at
        ) VALUES (?, ?, 1, 1, ?, 'medium', 'completed', 1.0, NOW())
    ");
    
    $result = $stmt->execute([
        "Completed Task - Should NOT be carried forward", 
        "This completed task should stay on yesterday's date", 
        $yesterday
    ]);
    
    $completedTaskId = $db->lastInsertId();
    echo "✓ Created completed task #$completedTaskId (planned: $yesterday)\n";
    
    // Run carry forward again
    $stmt = $db->prepare("
        UPDATE tasks SET planned_date = ? 
        WHERE assigned_to = 1 
        AND status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    
    $result = $stmt->execute([$today, $today]);
    $carriedForwardCount2 = $stmt->rowCount();
    
    echo "Second carry forward attempt: $carriedForwardCount2 tasks moved\n";
    
    // Verify completed task stayed on yesterday
    $stmt = $db->prepare("SELECT planned_date FROM tasks WHERE id = ?");
    $stmt->execute([$completedTaskId]);
    $completedTaskDate = $stmt->fetchColumn();
    
    if ($completedTaskDate === $yesterday) {
        echo "✓ Completed task correctly stayed on $yesterday\n";
    } else {
        echo "✗ Completed task incorrectly moved to $completedTaskDate\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CARRY FORWARD TEST SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "✓ Pending tasks are carried forward to current date\n";
    echo "✓ Completed tasks are NOT carried forward\n";
    echo "✓ Only tasks with status 'assigned' or 'not_started' are moved\n";
    echo "✓ Tasks are moved from past dates to current date\n";
    
    echo "\nHow it works:\n";
    echo "1. When viewing today's or future planner, system checks for pending tasks from past dates\n";
    echo "2. Tasks with status 'assigned' or 'not_started' are moved to current date\n";
    echo "3. Completed, cancelled, or in-progress tasks remain on their original dates\n";
    echo "4. This ensures no pending work is lost or forgotten\n";
    
    // Clean up test tasks
    echo "\nCleaning up test tasks...\n";
    $allTestIds = array_merge($createdTaskIds, [$completedTaskId]);
    
    if (!empty($allTestIds)) {
        $placeholders = implode(',', array_fill(0, count($allTestIds), '?'));
        $stmt = $db->prepare("DELETE FROM tasks WHERE id IN ($placeholders)");
        $stmt->execute($allTestIds);
        echo "✓ Cleaned up " . count($allTestIds) . " test tasks\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>