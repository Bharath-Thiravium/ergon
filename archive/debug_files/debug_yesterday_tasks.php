<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

$userId = $_SESSION['user_id'] ?? 1;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

echo "<h2>Debug: Yesterday's Tasks Issue</h2>";
echo "<p>User ID: {$userId}</p>";
echo "<p>Yesterday: {$yesterday}</p>";
echo "<p>Today: {$today}</p>";

$db = Database::connect();

// Check daily_tasks table for yesterday
echo "<h3>1. Daily Tasks for Yesterday ({$yesterday})</h3>";
$stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
$stmt->execute([$userId, $yesterday]);
$yesterdayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($yesterdayTasks)) {
    echo "<p style='color: red;'>❌ No tasks found in daily_tasks for yesterday</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($yesterdayTasks) . " tasks for yesterday</p>";
    foreach ($yesterdayTasks as $task) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
        echo "<strong>ID:</strong> {$task['id']}<br>";
        echo "<strong>Title:</strong> {$task['title']}<br>";
        echo "<strong>Status:</strong> {$task['status']}<br>";
        echo "<strong>Rollover Source:</strong> " . ($task['rollover_source_date'] ?? 'None') . "<br>";
        echo "<strong>Created:</strong> {$task['created_at']}<br>";
        echo "</div>";
    }
}

// Check if tasks were rolled over to today
echo "<h3>2. Rolled Over Tasks for Today ({$today})</h3>";
$stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ? AND rollover_source_date = ?");
$stmt->execute([$userId, $today, $yesterday]);
$rolledTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rolledTasks)) {
    echo "<p style='color: orange;'>⚠️ No tasks rolled over from yesterday to today</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($rolledTasks) . " tasks rolled over from yesterday</p>";
    foreach ($rolledTasks as $task) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
        echo "<strong>ID:</strong> {$task['id']}<br>";
        echo "<strong>Title:</strong> {$task['title']}<br>";
        echo "<strong>Status:</strong> {$task['status']}<br>";
        echo "<strong>Rollover Source:</strong> {$task['rollover_source_date']}<br>";
        echo "</div>";
    }
}

// Check regular tasks table for yesterday
echo "<h3>3. Regular Tasks for Yesterday ({$yesterday})</h3>";
$stmt = $db->prepare("
    SELECT * FROM tasks 
    WHERE (assigned_to = ? OR assigned_by = ?) 
    AND (
        DATE(COALESCE(planned_date, deadline, created_at)) = ? OR
        DATE(deadline) = ? OR
        DATE(created_at) = ?
    )
");
$stmt->execute([$userId, $userId, $yesterday, $yesterday, $yesterday]);
$regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($regularTasks)) {
    echo "<p style='color: red;'>❌ No tasks found in regular tasks table for yesterday</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($regularTasks) . " regular tasks for yesterday</p>";
    foreach ($regularTasks as $task) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
        echo "<strong>ID:</strong> {$task['id']}<br>";
        echo "<strong>Title:</strong> {$task['title']}<br>";
        echo "<strong>Status:</strong> {$task['status']}<br>";
        echo "<strong>Deadline:</strong> " . ($task['deadline'] ?? 'None') . "<br>";
        echo "<strong>Created:</strong> {$task['created_at']}<br>";
        echo "</div>";
    }
}

// Check all daily_tasks for this user
echo "<h3>4. All Daily Tasks for User {$userId}</h3>";
$stmt = $db->prepare("SELECT scheduled_date, COUNT(*) as count FROM daily_tasks WHERE user_id = ? GROUP BY scheduled_date ORDER BY scheduled_date DESC LIMIT 10");
$stmt->execute([$userId]);
$allDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($allDates)) {
    echo "<p style='color: red;'>❌ No daily tasks found for this user at all</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Date</th><th>Task Count</th></tr>";
    foreach ($allDates as $date) {
        $highlight = ($date['scheduled_date'] === $yesterday) ? 'background: yellow;' : '';
        echo "<tr style='{$highlight}'><td>{$date['scheduled_date']}</td><td>{$date['count']}</td></tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Quick Fix Options:</h3>";
echo "<a href='?action=create_test_task' style='padding: 10px; background: #007cba; color: white; text-decoration: none; margin: 5px;'>Create Test Task for Yesterday</a>";
echo "<a href='?action=force_fetch' style='padding: 10px; background: #28a745; color: white; text-decoration: none; margin: 5px;'>Force Fetch Tasks for Yesterday</a>";

// Handle quick fix actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'create_test_task') {
        $stmt = $db->prepare("
            INSERT INTO daily_tasks 
            (user_id, title, description, scheduled_date, priority, status, created_at)
            VALUES (?, 'Test Task for Yesterday', 'This is a test task created for debugging', ?, 'medium', 'not_started', NOW())
        ");
        $result = $stmt->execute([$userId, $yesterday]);
        if ($result) {
            echo "<p style='color: green;'>✅ Test task created for yesterday</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test task</p>";
        }
    }
    
    if ($_GET['action'] === 'force_fetch') {
        require_once __DIR__ . '/app/models/DailyPlanner.php';
        $planner = new DailyPlanner();
        $count = $planner->fetchAssignedTasksForDate($userId, $yesterday);
        echo "<p style='color: green;'>✅ Fetched {$count} tasks for yesterday</p>";
    }
}
?>