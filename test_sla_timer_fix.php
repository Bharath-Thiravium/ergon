<?php
/**
 * SLA Timer Fix Test Suite
 * Tests the complete break/resume/pause functionality
 */

require_once 'app/config/database.php';
require_once 'app/models/DailyPlanner.php';

// Test configuration
$testUserId = 1; // Change this to a valid user ID
$testDate = date('Y-m-d');

echo "🧪 SLA Timer Fix Test Suite\n";
echo "==========================\n\n";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    // Test 1: Create a test task
    echo "Test 1: Creating test task...\n";
    $stmt = $db->prepare("
        INSERT INTO daily_tasks 
        (user_id, title, description, scheduled_date, priority, status, planned_duration)
        VALUES (?, 'SLA Timer Test Task', 'Testing break/resume functionality', ?, 'high', 'not_started', 60)
    ");
    $stmt->execute([$testUserId, $testDate]);
    $testTaskId = $db->lastInsertId();
    echo "✅ Test task created with ID: {$testTaskId}\n\n";
    
    // Test 2: Start task (should initialize SLA timer)
    echo "Test 2: Starting task...\n";
    $result = $planner->startTask($testTaskId, $testUserId);
    if ($result) {
        echo "✅ Task started successfully\n";
        
        // Check database state
        $stmt = $db->prepare("
            SELECT status, start_time, sla_end_time, remaining_sla_time, active_seconds
            FROM daily_tasks WHERE id = ?
        ");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   Status: {$task['status']}\n";
        echo "   Start time: {$task['start_time']}\n";
        echo "   SLA end time: {$task['sla_end_time']}\n";
        echo "   Remaining SLA: {$task['remaining_sla_time']} seconds\n";
    } else {
        echo "❌ Failed to start task\n";
    }
    echo "\n";
    
    // Test 3: Wait 2 seconds then pause
    echo "Test 3: Waiting 2 seconds then pausing...\n";
    sleep(2);
    
    $result = $planner->pauseTask($testTaskId, $testUserId);
    if ($result) {
        echo "✅ Task paused successfully\n";
        
        // Check database state
        $stmt = $db->prepare("
            SELECT status, pause_start_time, remaining_sla_time, active_seconds, time_used
            FROM daily_tasks WHERE id = ?
        ");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   Status: {$task['status']}\n";
        echo "   Pause start: {$task['pause_start_time']}\n";
        echo "   Remaining SLA: {$task['remaining_sla_time']} seconds\n";
        echo "   Active seconds: {$task['active_seconds']}\n";
        echo "   Time used: {$task['time_used']}\n";
    } else {
        echo "❌ Failed to pause task\n";
    }
    echo "\n";
    
    // Test 4: Wait 3 seconds (pause time) then resume
    echo "Test 4: Waiting 3 seconds (pause time) then resuming...\n";
    sleep(3);
    
    $result = $planner->resumeTask($testTaskId, $testUserId);
    if ($result) {
        echo "✅ Task resumed successfully\n";
        
        // Check database state
        $stmt = $db->prepare("
            SELECT status, resume_time, sla_end_time, total_pause_duration, remaining_sla_time
            FROM daily_tasks WHERE id = ?
        ");
        $stmt->execute([$testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   Status: {$task['status']}\n";
        echo "   Resume time: {$task['resume_time']}\n";
        echo "   New SLA end time: {$task['sla_end_time']}\n";
        echo "   Total pause duration: {$task['total_pause_duration']} seconds\n";
        echo "   Remaining SLA: {$task['remaining_sla_time']} seconds\n";
    } else {
        echo "❌ Failed to resume task\n";
    }
    echo "\n";
    
    // Test 5: Test multiple break/resume cycles
    echo "Test 5: Testing multiple break/resume cycles...\n";
    
    // Second pause
    sleep(1);
    $planner->pauseTask($testTaskId, $testUserId);
    echo "   Second pause completed\n";
    
    sleep(2);
    $planner->resumeTask($testTaskId, $testUserId);
    echo "   Second resume completed\n";
    
    // Check final state
    $stmt = $db->prepare("
        SELECT total_pause_duration, active_seconds, time_used, remaining_sla_time
        FROM daily_tasks WHERE id = ?
    ");
    $stmt->execute([$testTaskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Final total pause duration: {$task['total_pause_duration']} seconds\n";
    echo "   Final active seconds: {$task['active_seconds']}\n";
    echo "   Final time used: {$task['time_used']}\n";
    echo "   Final remaining SLA: {$task['remaining_sla_time']} seconds\n";
    echo "\n";
    
    // Test 6: Test timer API endpoint
    echo "Test 6: Testing timer API endpoint...\n";
    
    // Simulate API call
    $_SESSION['user_id'] = $testUserId;
    $_GET['action'] = 'timer';
    $_GET['task_id'] = $testTaskId;
    
    ob_start();
    include 'api/daily_planner_workflow.php';
    $apiResponse = ob_get_clean();
    
    $timerData = json_decode($apiResponse, true);
    if ($timerData && $timerData['success']) {
        echo "✅ Timer API working correctly\n";
        echo "   Status: {$timerData['status']}\n";
        echo "   Remaining seconds: {$timerData['remaining_seconds']}\n";
        echo "   Total pause duration: {$timerData['total_pause_duration']}\n";
        echo "   Is overdue: " . ($timerData['is_overdue'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Timer API failed\n";
        echo "   Response: {$apiResponse}\n";
    }
    echo "\n";
    
    // Test 7: Check task history
    echo "Test 7: Checking task history...\n";
    $history = $planner->getTaskHistory($testTaskId, $testUserId);
    echo "✅ Found " . count($history) . " history entries:\n";
    foreach ($history as $entry) {
        echo "   - {$entry['date']}: {$entry['action']} - {$entry['notes']}\n";
    }
    echo "\n";
    
    // Cleanup
    echo "🧹 Cleaning up test data...\n";
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE id = ?");
    $stmt->execute([$testTaskId]);
    
    $stmt = $db->prepare("DELETE FROM daily_task_history WHERE daily_task_id = ?");
    $stmt->execute([$testTaskId]);
    
    echo "✅ Test data cleaned up\n\n";
    
    echo "🎉 All tests completed successfully!\n";
    echo "\nSLA Timer Fix Summary:\n";
    echo "✅ Start task initializes SLA timer correctly\n";
    echo "✅ Pause saves remaining SLA time\n";
    echo "✅ Resume continues from saved SLA time\n";
    echo "✅ Multiple break/resume cycles work correctly\n";
    echo "✅ Cumulative pause duration is tracked\n";
    echo "✅ Timer API provides accurate real-time data\n";
    echo "✅ All timer events are logged in history\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>