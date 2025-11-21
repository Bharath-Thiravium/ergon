<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

$userId = $_SESSION['user_id'] ?? 16;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

echo "<h2>Restore Yesterday's Tasks</h2>";

$db = Database::connect();

// Find rolled-over tasks from yesterday to today
$stmt = $db->prepare("
    SELECT * FROM daily_tasks 
    WHERE user_id = ? AND scheduled_date = ? AND rollover_source_date = ?
");
$stmt->execute([$userId, $today, $yesterday]);
$rolledTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Found " . count($rolledTasks) . " tasks rolled over from yesterday</h3>";

if (!empty($rolledTasks)) {
    echo "<h3>Recreating original tasks for yesterday...</h3>";
    
    foreach ($rolledTasks as $task) {
        // Check if original task already exists for yesterday
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM daily_tasks 
            WHERE user_id = ? AND scheduled_date = ? AND title = ? AND rollover_source_date IS NULL
        ");
        $checkStmt->execute([$userId, $yesterday, $task['title']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            // Create original task entry for yesterday
            $insertStmt = $db->prepare("
                INSERT INTO daily_tasks 
                (user_id, task_id, original_task_id, title, description, scheduled_date, 
                 priority, status, planned_duration, source_field, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', ?, 'restored', ?)
            ");
            
            $result = $insertStmt->execute([
                $userId,
                $task['task_id'],
                $task['original_task_id'],
                $task['title'],
                $task['description'],
                $yesterday,
                $task['priority'],
                $task['planned_duration'],
                $yesterday . ' 09:00:00'  // Set created time to yesterday
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Restored: {$task['title']}</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to restore: {$task['title']}</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Already exists: {$task['title']}</p>";
        }
    }
}

// Verify the restoration
require_once __DIR__ . '/app/models/DailyPlanner.php';
$planner = new DailyPlanner();
$yesterdayTasks = $planner->getTasksForDate($userId, $yesterday);

echo "<h3>Yesterday now has " . count($yesterdayTasks) . " tasks:</h3>";
foreach ($yesterdayTasks as $task) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
    echo "<strong>Title:</strong> {$task['title']}<br>";
    echo "<strong>Priority:</strong> {$task['priority']}<br>";
    echo "<strong>Status:</strong> {$task['status']}<br>";
    echo "<strong>Indicator:</strong> {$task['task_indicator']}<br>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/ergon/workflow/daily-planner/{$yesterday}'>View Yesterday's Tasks in Daily Planner</a></p>";
?>