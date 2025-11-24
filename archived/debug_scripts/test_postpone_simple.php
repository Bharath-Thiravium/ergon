<?php
// Simple test to verify postpone fix
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    echo "<h2>Postpone Fix Test Results</h2>";
    
    // Test the postponeTask method directly
    $userId = 1; // Adjust as needed
    $today = date('Y-m-d');
    $futureDate = date('Y-m-d', strtotime('+2 days'));
    
    // Create a test task
    $stmt = $db->prepare("
        INSERT INTO daily_tasks 
        (user_id, original_task_id, title, description, scheduled_date, status, created_at)
        VALUES (?, 999998, 'Test Postpone Fix', 'Testing the postpone fix', ?, 'not_started', NOW())
    ");
    $stmt->execute([$userId, $today]);
    $testTaskId = $db->lastInsertId();
    
    echo "<p>✓ Created test task ID: $testTaskId</p>";
    
    // Test 1: First postpone
    try {
        $result1 = $planner->postponeTask($testTaskId, $userId, $futureDate);
        if ($result1) {
            echo "<p>✓ First postpone successful</p>";
        } else {
            echo "<p>✗ First postpone failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>✗ First postpone error: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Second postpone to same date (should work now)
    try {
        $result2 = $planner->postponeTask($testTaskId, $userId, $futureDate);
        if ($result2) {
            echo "<p>✓ Second postpone to same date successful (fix working!)</p>";
        } else {
            echo "<p>✗ Second postpone failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Second postpone error: " . $e->getMessage() . "</p>";
    }
    
    // Cleanup
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE original_task_id = 999998");
    $stmt->execute();
    echo "<p>✓ Test data cleaned up</p>";
    
    echo "<h3>Fix Summary:</h3>";
    echo "<ul>";
    echo "<li>Removed the problematic postponed_to_date check that was causing false positives</li>";
    echo "<li>Modified the duplicate check to exclude postponed tasks</li>";
    echo "<li>Tasks can now be postponed multiple times to the same date</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>✗ Test failed: " . $e->getMessage() . "</p>";
}
?>