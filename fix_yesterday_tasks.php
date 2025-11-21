<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

$userId = $_SESSION['user_id'] ?? 16;
$yesterday = date('Y-m-d', strtotime('-1 day'));

echo "<h2>Fix Yesterday's Tasks</h2>";
echo "<p>User ID: {$userId}</p>";
echo "<p>Yesterday: {$yesterday}</p>";

$planner = new DailyPlanner();

// Force fetch tasks for yesterday
echo "<h3>Fetching tasks for yesterday...</h3>";
$count = $planner->fetchAssignedTasksForDate($userId, $yesterday);
echo "<p>✅ Fetched {$count} tasks for yesterday</p>";

// Get tasks for yesterday to verify
$tasks = $planner->getTasksForDate($userId, $yesterday);
echo "<h3>Tasks now available for yesterday:</h3>";

if (empty($tasks)) {
    echo "<p style='color: red;'>❌ Still no tasks found. Creating a sample task...</p>";
    
    // Create a sample task for yesterday
    $db = Database::connect();
    $stmt = $db->prepare("
        INSERT INTO daily_tasks 
        (user_id, title, description, scheduled_date, priority, status, source_field, created_at)
        VALUES (?, ?, ?, ?, 'medium', 'not_started', 'manual_fix', NOW())
    ");
    $result = $stmt->execute([
        $userId, 
        'Sample Task for Yesterday', 
        'This task was created to demonstrate yesterday\'s task functionality', 
        $yesterday
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Sample task created for yesterday</p>";
        $tasks = $planner->getTasksForDate($userId, $yesterday);
    }
}

if (!empty($tasks)) {
    echo "<p style='color: green;'>✅ Found " . count($tasks) . " tasks for yesterday</p>";
    foreach ($tasks as $task) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
        echo "<strong>Title:</strong> {$task['title']}<br>";
        echo "<strong>Status:</strong> {$task['status']}<br>";
        echo "<strong>Indicator:</strong> {$task['task_indicator']}<br>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='/ergon/workflow/daily-planner/{$yesterday}'>View Yesterday's Tasks in Daily Planner</a></p>";
echo "<p><a href='/ergon/workflow/daily-planner/" . date('Y-m-d') . "'>View Today's Tasks in Daily Planner</a></p>";
?>