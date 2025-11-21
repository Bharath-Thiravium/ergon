<?php
require_once 'app/config/database.php';
require_once 'app/models/DailyPlanner.php';

// Test postponed task display logic
$userId = 1; // Change to your user ID
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "<h2>Testing Postponed Task Display Logic</h2>";

try {
    $planner = new DailyPlanner();
    
    echo "<h3>Tasks for Today ($today):</h3>";
    $todayTasks = $planner->getTasksForDate($userId, $today);
    foreach ($todayTasks as $task) {
        echo "ID: {$task['id']}, Title: {$task['title']}, Status: {$task['status']}, Context: {$task['postpone_context']}<br>";
        if ($task['status'] === 'postponed') {
            echo "  - Postponed from: {$task['postponed_from_date']}, Postponed to: {$task['postponed_to_date']}<br>";
        }
    }
    
    echo "<h3>Tasks for Tomorrow ($tomorrow):</h3>";
    $tomorrowTasks = $planner->getTasksForDate($userId, $tomorrow);
    foreach ($tomorrowTasks as $task) {
        echo "ID: {$task['id']}, Title: {$task['title']}, Status: {$task['status']}, Context: {$task['postpone_context']}<br>";
        if ($task['status'] === 'postponed') {
            echo "  - Postponed from: {$task['postponed_from_date']}, Postponed to: {$task['postponed_to_date']}<br>";
        }
    }
    
    echo "<h3>Database Structure Check:</h3>";
    $db = Database::connect();
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks WHERE Field IN ('postponed_from_date', 'postponed_to_date', 'pause_duration')");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']}, Type: {$col['Type']}<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>