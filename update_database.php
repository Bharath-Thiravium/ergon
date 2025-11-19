<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Database Update Script</h2>";

try {
    $db = Database::connect();
    
    echo "<p>Checking database structure...</p>";
    
    // Check if total_pause_duration exists
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'total_pause_duration'");
    $stmt->execute();
    $hasTotalPause = $stmt->fetch();
    
    // Check if pause_duration exists
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'pause_duration'");
    $stmt->execute();
    $hasPause = $stmt->fetch();
    
    if ($hasTotalPause && !$hasPause) {
        // Rename total_pause_duration to pause_duration
        $db->exec("ALTER TABLE daily_tasks CHANGE COLUMN total_pause_duration pause_duration INT DEFAULT 0");
        echo "<p style='color: green;'>✓ Renamed total_pause_duration to pause_duration</p>";
    } elseif (!$hasTotalPause && !$hasPause) {
        // Add pause_duration column
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0");
        echo "<p style='color: green;'>✓ Added pause_duration column</p>";
    } elseif ($hasPause) {
        echo "<p style='color: blue;'>✓ pause_duration column already exists</p>";
    }
    
    // Ensure all pause_duration values are not null
    $db->exec("UPDATE daily_tasks SET pause_duration = COALESCE(pause_duration, 0) WHERE pause_duration IS NULL");
    
    // Check for any tasks currently on break and reset their pause tracking
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE status = 'on_break'");
    $stmt->execute();
    $breakTasks = $stmt->fetchColumn();
    
    if ($breakTasks > 0) {
        echo "<p style='color: orange;'>Found {$breakTasks} tasks on break - resetting their pause tracking</p>";
        $db->exec("UPDATE daily_tasks SET pause_time = NOW() WHERE status = 'on_break' AND pause_time IS NULL");
    }
    
    echo "<p style='color: green;'><strong>✓ Database structure updated successfully!</strong></p>";
    echo "<p><a href='/ergon/workflow/daily-planner'>Return to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>