<?php
// Direct SQL test without using DailyPlanner class
echo "<h2>Direct SQL Test</h2>\n";

// Simple database connection without using the Database class
try {
    $host = 'localhost';
    $dbname = 'ergon';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $testUserId = 1;
    $testDate = '2025-11-25';
    
    echo "Testing with User ID: {$testUserId}, Date: {$testDate}<br>\n";
    
    // Create a test task
    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, planned_date, status, priority, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        'Direct SQL Test Task',
        'This should appear on ' . $testDate,
        $testUserId,
        $testUserId,
        $testDate,
        'assigned',
        'medium',
        0.25
    ]);
    
    if (!$result) {
        echo "❌ Failed to create test task<br>\n";
        exit;
    }
    
    $testTaskId = $pdo->lastInsertId();
    echo "✅ Created test task ID {$testTaskId}<br>\n";
    
    // Test the exact query used by fetchAssignedTasksForDate for future dates
    $stmt = $pdo->prepare("
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
        AND t.status NOT IN ('completed', 'cancelled')
        AND (
            -- PRIORITY 1: Tasks specifically planned for this future date
            DATE(t.planned_date) = ? OR
            -- PRIORITY 2: Tasks with deadline on this future date but no planned_date
            (DATE(t.deadline) = ? AND (t.planned_date IS NULL OR t.planned_date = ''))
        )
    ");
    
    $stmt->execute([$testDate, $testDate, $testUserId, $testDate, $testDate]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($tasks) . " tasks with the query<br>\n";
    
    foreach ($tasks as $task) {
        echo "- Task ID {$task['id']}: '{$task['title']}' (status: {$task['status']}, source: {$task['source_field']})<br>\n";
    }
    
    if (count($tasks) > 0) {
        echo "✅ SUCCESS: Query found the test task!<br>\n";
    } else {
        echo "❌ FAILED: Query did not find the test task<br>\n";
        
        // Debug: Check what the task looks like in the database
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            echo "Task in database:<br>\n";
            echo "- ID: {$task['id']}<br>\n";
            echo "- Title: {$task['title']}<br>\n";
            echo "- Assigned to: {$task['assigned_to']}<br>\n";
            echo "- Status: {$task['status']}<br>\n";
            echo "- Planned date: {$task['planned_date']}<br>\n";
            echo "- DATE(planned_date): " . date('Y-m-d', strtotime($task['planned_date'])) . "<br>\n";
        }
    }
    
    // Cleanup
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    
    echo "<br>🧹 Test completed and cleaned up<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}
?>