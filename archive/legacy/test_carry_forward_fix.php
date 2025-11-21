<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $userId = 1; // Change to your user ID
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Testing Carry Forward Fix\n";
    echo "========================\n";
    echo "User ID: $userId\n";
    echo "Today: $today\n";
    echo "Yesterday: $yesterday\n\n";
    
    // 1. Create a test task with yesterday's date
    echo "1. Creating test task with yesterday's planned date...\n";
    $testTitle = "Test Carry Forward Task - " . date('H:i:s');
    
    $stmt = $db->prepare("
        INSERT INTO tasks (title, description, assigned_by, assigned_to, planned_date, status, priority, sla_hours, created_at) 
        VALUES (?, 'Test task for carry forward', ?, ?, ?, 'assigned', 'medium', 1.0, NOW())
    ");
    $result = $stmt->execute([$testTitle, $userId, $userId, $yesterday]);
    
    if ($result) {
        $testTaskId = $db->lastInsertId();
        echo "   ✓ Created test task #$testTaskId: '$testTitle'\n";
        echo "   Planned date: $yesterday\n";
        echo "   Status: assigned\n";
    } else {
        echo "   ✗ Failed to create test task\n";
        exit;
    }
    
    // 2. Test manual carry forward
    echo "\n2. Testing carry forward logic...\n";
    $stmt = $db->prepare("
        UPDATE tasks SET planned_date = ? 
        WHERE assigned_to = ? 
        AND status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    $result = $stmt->execute([$today, $userId, $today]);
    $movedCount = $stmt->rowCount();
    
    echo "   Moved $movedCount tasks to today\n";
    
    if ($movedCount > 0) {
        echo "   ✓ Carry forward is working!\n";
    } else {
        echo "   ✗ No tasks were moved\n";
    }
    
    // 3. Verify the task was moved
    echo "\n3. Verifying task was moved to today...\n";
    $stmt = $db->prepare("SELECT planned_date FROM tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    $newPlannedDate = $stmt->fetchColumn();
    
    if ($newPlannedDate === $today) {
        echo "   ✓ Task #$testTaskId moved to today ($today)\n";
    } else {
        echo "   ✗ Task #$testTaskId still on $newPlannedDate\n";
    }
    
    // 4. Check what appears in today's planner query
    echo "\n4. Checking today's planner query...\n";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, status 
        FROM tasks 
        WHERE assigned_to = ? 
        AND status != 'completed'
        AND (
            planned_date = ? OR 
            (planned_date IS NULL AND DATE(created_at) = ?)
        )
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayTasks)) {
        echo "   No tasks found for today's planner\n";
    } else {
        echo "   Tasks that should appear in today's planner:\n";
        foreach ($todayTasks as $task) {
            $indicator = ($task['id'] == $testTaskId) ? " ← TEST TASK" : "";
            echo "   - #{$task['id']}: {$task['title']} (planned: {$task['planned_date']})$indicator\n";
        }
    }
    
    // 5. Clean up
    echo "\n5. Cleaning up test task...\n";
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    echo "   ✓ Test task removed\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "CARRY FORWARD TEST COMPLETE\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\nNow visit the daily planner to see if carry forward works:\n";
    echo "http://localhost/ergon/workflow/daily-planner\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>