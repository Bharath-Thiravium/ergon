<?php
session_start();
require_once 'app/config/database.php';
require_once 'app/models/DailyPlanner.php';

$_SESSION['user_id'] = 16;

try {
    $planner = new DailyPlanner();
    
    echo "<h1>Direct DailyPlanner Method Test</h1>";
    
    // Test resume task 212 (on_break)
    echo "<h3>Testing Resume Task 212</h3>";
    try {
        $result = $planner->resumeTask(212, 16);
        echo "<p>Result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    } catch (Exception $e) {
        echo "<p>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test pause task 209 (in_progress)  
    echo "<h3>Testing Pause Task 209</h3>";
    try {
        $result = $planner->pauseTask(209, 16);
        echo "<p>Result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    } catch (Exception $e) {
        echo "<p>Exception: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Setup Error: " . $e->getMessage() . "</p>";
}
?>