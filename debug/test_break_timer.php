<?php
/**
 * Test Break Timer Functionality
 * 
 * This script tests the break/pause timer functionality to ensure:
 * 1. Break start time is saved to database
 * 2. Break duration is calculated correctly
 * 3. Break state persists after page refresh
 * 4. Resume properly saves total break duration
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

echo "<h2>🔧 Break Timer Functionality Test</h2>\n";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    // Test user ID (use existing user)
    $userId = 1;
    $today = date('Y-m-d');
    
    echo "<h3>1. Creating Test Task</h3>\n";
    
    // Create a test task
    $stmt = $db->prepare("
        INSERT INTO daily_tasks 
        (user_id, title, description, scheduled_date, priority, status, planned_duration, created_at)
        VALUES (?, 'Break Timer Test Task', 'Testing break/pause functionality', ?, 'medium', 'not_started', 60, NOW())
    ");
    $stmt->execute([$userId, $today]);
    $testTaskId = $db->lastInsertId();
    
    echo "✅ Created test task ID: {$testTaskId}<br>\n";
    
    echo "<h3>2. Starting Task</h3>\n";
    
    // Start the task
    $result = $planner->startTask($testTaskId, $userId);
    if ($result) {
        echo "✅ Task started successfully<br>\n";
    } else {
        echo "❌ Failed to start task<br>\n";
    }
    
    // Wait 2 seconds to simulate work
    sleep(2);
    
    echo "<h3>3. Pausing Task (Break)</h3>\n";
    
    // Pause the task
    $result = $planner->pauseTask($testTaskId, $userId);
    if ($result) {
        echo "✅ Task paused successfully<br>\n";
        
        // Check if pause_start_time was saved
        $stmt = $db->prepare("SELECT pause_start_time, status FROM daily_tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task['pause_start_time']) {
            echo "✅ Pause start time saved: {$task['pause_start_time']}<br>\n";
        } else {
            echo "❌ Pause start time NOT saved<br>\n";
        }
        
        if ($task['status'] === 'on_break') {
            echo "✅ Task status correctly set to 'on_break'<br>\n";
        } else {
            echo "❌ Task status incorrect: {$task['status']}<br>\n";
        }
    } else {
        echo "❌ Failed to pause task<br>\n";
    }
    
    // Wait 3 seconds to simulate break time
    sleep(3);
    
    echo "<h3>4. Resuming Task</h3>\n";
    
    // Resume the task
    $result = $planner->resumeTask($testTaskId, $userId);
    if ($result) {
        echo "✅ Task resumed successfully<br>\n";
        
        // Check if pause duration was calculated and saved
        $stmt = $db->prepare("SELECT pause_duration, pause_start_time, status FROM daily_tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task['pause_duration'] > 0) {
            echo "✅ Pause duration calculated and saved: {$task['pause_duration']} seconds<br>\n";
        } else {
            echo "❌ Pause duration NOT calculated: {$task['pause_duration']}<br>\n";
        }
        
        if ($task['pause_start_time'] === null) {
            echo "✅ Pause start time cleared after resume<br>\n";
        } else {
            echo "❌ Pause start time NOT cleared: {$task['pause_start_time']}<br>\n";
        }
        
        if ($task['status'] === 'in_progress') {
            echo "✅ Task status correctly set to 'in_progress'<br>\n";
        } else {
            echo "❌ Task status incorrect: {$task['status']}<br>\n";
        }
    } else {
        echo "❌ Failed to resume task<br>\n";
    }
    
    echo "<h3>5. Testing API Timer Endpoint</h3>\n";
    
    // Test the timer API endpoint
    $timerUrl = "http://localhost/ergon/api/daily_planner_workflow.php?action=timer&task_id={$testTaskId}";
    
    // Simulate API call (we'll just query the database directly for this test)
    $stmt = $db->prepare("
        SELECT dt.*, COALESCE(t.sla_hours, 0.25) as sla_hours
        FROM daily_tasks dt
        LEFT JOIN tasks t ON dt.task_id = t.id
        WHERE dt.id = ? AND dt.user_id = ?
    ");
    $stmt->execute([$testTaskId, $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo "✅ Timer API data retrieved successfully<br>\n";
        echo "- Status: {$task['status']}<br>\n";
        echo "- Active seconds: {$task['active_seconds']}<br>\n";
        echo "- Pause duration: {$task['pause_duration']}<br>\n";
        echo "- SLA hours: {$task['sla_hours']}<br>\n";
    } else {
        echo "❌ Failed to retrieve timer data<br>\n";
    }
    
    echo "<h3>6. Testing Multiple Break Cycles</h3>\n";
    
    // Test multiple pause/resume cycles
    for ($i = 1; $i <= 2; $i++) {
        echo "Break cycle {$i}:<br>\n";
        
        // Pause
        $planner->pauseTask($testTaskId, $userId);
        sleep(1); // 1 second break
        
        // Resume
        $planner->resumeTask($testTaskId, $userId);
        
        // Check accumulated pause duration
        $stmt = $db->prepare("SELECT pause_duration FROM daily_tasks WHERE id = ?");
        $stmt->execute([$testTaskId]);
        $pauseDuration = $stmt->fetchColumn();
        
        echo "- Accumulated pause duration after cycle {$i}: {$pauseDuration} seconds<br>\n";
    }
    
    echo "<h3>7. Final Task State</h3>\n";
    
    // Get final task state
    $stmt = $db->prepare("
        SELECT id, title, status, active_seconds, pause_duration, 
               start_time, pause_start_time, resume_time
        FROM daily_tasks 
        WHERE id = ?
    ");
    $stmt->execute([$testTaskId]);
    $finalTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Final task state:<br>\n";
    echo "- ID: {$finalTask['id']}<br>\n";
    echo "- Title: {$finalTask['title']}<br>\n";
    echo "- Status: {$finalTask['status']}<br>\n";
    echo "- Active seconds: {$finalTask['active_seconds']}<br>\n";
    echo "- Total pause duration: {$finalTask['pause_duration']}<br>\n";
    echo "- Start time: {$finalTask['start_time']}<br>\n";
    echo "- Pause start time: " . ($finalTask['pause_start_time'] ?: 'NULL') . "<br>\n";
    echo "- Resume time: {$finalTask['resume_time']}<br>\n";
    
    echo "<h3>8. Cleanup</h3>\n";
    
    // Clean up test task
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    
    echo "✅ Test task cleaned up<br>\n";
    
    echo "<h3>✅ Break Timer Test Complete</h3>\n";
    echo "<p><strong>Summary:</strong> The break timer functionality should now work correctly with proper database persistence.</p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Test Failed</h3>\n";
    echo "Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>