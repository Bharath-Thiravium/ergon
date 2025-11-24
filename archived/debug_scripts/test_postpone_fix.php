<?php
/**
 * Test script to verify postpone functionality fix
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    echo "=== Testing Postpone Functionality Fix ===\n\n";
    
    // Test user ID (adjust as needed)
    $userId = 1;
    $today = date('Y-m-d');
    $futureDate = date('Y-m-d', strtotime('+3 days'));
    
    // 1. Create a test task for today
    $stmt = $db->prepare("
        INSERT INTO daily_tasks 
        (user_id, task_id, original_task_id, title, description, scheduled_date, status, created_at)
        VALUES (?, NULL, ?, 'Test Postpone Task', 'Testing postpone functionality', ?, 'not_started', NOW())
    ");
    $stmt->execute([$userId, 999999, $today]);
    $testTaskId = $db->lastInsertId();
    
    echo "✓ Created test task ID: $testTaskId for date: $today\n";
    
    // 2. Test postponing the task
    echo "→ Attempting to postpone task to: $futureDate\n";
    
    $result = $planner->postponeTask($testTaskId, $userId, $futureDate);
    
    if ($result) {
        echo "✓ Task postponed successfully!\n";
        
        // Verify the postponed task was created
        $stmt = $db->prepare("
            SELECT * FROM daily_tasks 
            WHERE user_id = ? AND scheduled_date = ? AND postponed_from_date = ?
        ");
        $stmt->execute([$userId, $futureDate, $today]);
        $postponedTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($postponedTask) {
            echo "✓ Postponed task found on target date: {$postponedTask['scheduled_date']}\n";
            echo "  - Status: {$postponedTask['status']}\n";
            echo "  - Postponed from: {$postponedTask['postponed_from_date']}\n";
        }
        
        // 3. Test postponing to the same date again (should work now)
        echo "\n→ Testing postpone to same date again...\n";
        
        try {
            $result2 = $planner->postponeTask($testTaskId, $userId, $futureDate);
            if ($result2) {
                echo "✓ Second postpone to same date worked (as expected)\n";
            }
        } catch (Exception $e) {
            echo "✗ Second postpone failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ Task postpone failed\n";
    }
    
    // 4. Cleanup test data
    echo "\n→ Cleaning up test data...\n";
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = ? OR task_id = ?");
    $stmt->execute([999999, 999999]);
    echo "✓ Test data cleaned up\n";
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}