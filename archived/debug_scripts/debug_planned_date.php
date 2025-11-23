<?php
// Debug script for planned_date functionality
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

echo "<h2>Debug Planned Date Functionality</h2>\n";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $testUserId = 1;
    $futureDate = date('Y-m-d', strtotime('+3 days'));
    
    echo "<h3>Step 1: Create test task</h3>\n";
    $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, planned_date, status, priority, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        'Debug Test Task',
        'This task should appear only on ' . $futureDate,
        $testUserId,
        $testUserId,
        $futureDate,
        'assigned',
        'medium',
        0.25
    ]);
    
    if (!$result) {
        echo "❌ Failed to create test task\n";
        exit;
    }
    
    $testTaskId = $db->lastInsertId();
    echo "✅ Created test task ID {$testTaskId} with planned_date = {$futureDate}<br>\n";
    
    echo "<h3>Step 2: Check fetchAssignedTasksForDate directly</h3>\n";
    
    // Test the fetchAssignedTasksForDate method directly
    $reflection = new ReflectionClass($planner);
    $method = $reflection->getMethod('fetchAssignedTasksForDate');
    $method->setAccessible(true);
    
    echo "Calling fetchAssignedTasksForDate for user {$testUserId} on date {$futureDate}<br>\n";
    $addedCount = $method->invoke($planner, $testUserId, $futureDate);
    echo "fetchAssignedTasksForDate returned: {$addedCount} tasks added<br>\n";
    
    echo "<h3>Step 3: Check daily_tasks table</h3>\n";
    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ? AND original_task_id = ?");
    $stmt->execute([$testUserId, $futureDate, $testTaskId]);
    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dailyTask) {
        echo "✅ Found daily_task record:<br>\n";
        echo "- ID: {$dailyTask['id']}<br>\n";
        echo "- Title: {$dailyTask['title']}<br>\n";
        echo "- Scheduled Date: {$dailyTask['scheduled_date']}<br>\n";
        echo "- Source Field: {$dailyTask['source_field']}<br>\n";
    } else {
        echo "❌ No daily_task record found<br>\n";
        
        // Check if task exists in tasks table
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            echo "✅ Task exists in tasks table:<br>\n";
            echo "- ID: {$task['id']}<br>\n";
            echo "- Title: {$task['title']}<br>\n";
            echo "- Planned Date: {$task['planned_date']}<br>\n";
            echo "- Assigned To: {$task['assigned_to']}<br>\n";
            echo "- Status: {$task['status']}<br>\n";
            
            // Check the SQL query that should match this task
            echo "<h4>Testing SQL Query</h4>\n";
            $stmt = $db->prepare("
                SELECT 
                    t.id, t.title, t.description, t.priority, t.status,
                    t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
                    CASE 
                        WHEN DATE(t.planned_date) = ? THEN 'planned_date'
                        WHEN DATE(t.deadline) = ? THEN 'deadline'
                        ELSE 'other'
                    END as source_field
                FROM tasks t
                WHERE t.assigned_to = ? 
                AND t.status NOT IN ('completed')
                AND (
                    DATE(t.planned_date) = ? OR
                    (DATE(t.deadline) = ? AND t.planned_date IS NULL)
                )
            ");
            $stmt->execute([$futureDate, $futureDate, $testUserId, $futureDate, $futureDate]);
            $matchedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($matchedTasks) {
                echo "✅ SQL query matches " . count($matchedTasks) . " tasks:<br>\n";
                foreach ($matchedTasks as $matchedTask) {
                    echo "- Task ID {$matchedTask['id']}: {$matchedTask['title']} (Source: {$matchedTask['source_field']})<br>\n";
                }
            } else {
                echo "❌ SQL query doesn't match any tasks<br>\n";
                
                // Debug the conditions
                echo "<h5>Debug Conditions:</h5>\n";
                echo "- t.assigned_to = {$testUserId}: " . ($task['assigned_to'] == $testUserId ? '✅' : '❌') . "<br>\n";
                echo "- t.status NOT IN ('completed'): " . ($task['status'] != 'completed' ? '✅' : '❌') . "<br>\n";
                echo "- DATE(t.planned_date) = {$futureDate}: " . ($task['planned_date'] == $futureDate ? '✅' : '❌') . "<br>\n";
            }
        } else {
            echo "❌ Task doesn't exist in tasks table<br>\n";
        }
    }
    
    echo "<h3>Step 4: Test getTasksForDate</h3>\n";
    $tasks = $planner->getTasksForDate($testUserId, $futureDate);
    echo "getTasksForDate returned " . count($tasks) . " tasks<br>\n";
    
    if ($tasks) {
        foreach ($tasks as $task) {
            echo "- Task: '{$task['title']}' (Original ID: {$task['original_task_id']}, Source: {$task['source_field']})<br>\n";
        }
    }
    
    // Clean up
    echo "<h3>Cleanup</h3>\n";
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = ?");
    $stmt->execute([$testTaskId]);
    echo "🧹 Cleaned up test task<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}
?>