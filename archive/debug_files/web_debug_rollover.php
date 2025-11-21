<!DOCTYPE html>
<html>
<head>
    <title>Rollover Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h2>Debugging Rollover Logic</h2>";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "<div class='section'>";
    echo "<h3>Current Status</h3>";
    echo "<p>User ID: {$userId}</p>";
    echo "<p>Today: {$today}</p>";
    echo "<p>Yesterday: {$yesterday}</p>";
    echo "</div>";
    
    // Check for uncompleted tasks from yesterday
    echo "<div class='section'>";
    echo "<h3>Step 1: Check Uncompleted Tasks from Yesterday</h3>";
    $stmt = $db->prepare("
        SELECT * FROM daily_tasks 
        WHERE scheduled_date = ? 
        AND status IN ('not_started', 'in_progress', 'postponed') 
        AND completed_percentage < 100
    ");
    $stmt->execute([$yesterday]);
    $uncompletedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($uncompletedTasks) . " uncompleted tasks from yesterday</p>";
    
    if (!empty($uncompletedTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Status</th><th>Progress</th><th>Original Task ID</th></tr>";
        foreach ($uncompletedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['user_id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>" . ($task['original_task_id'] ?? $task['task_id'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check existing tasks for today
    echo "<div class='section'>";
    echo "<h3>Step 2: Check Existing Tasks for Today (Before Rollover)</h3>";
    $stmt = $db->prepare("
        SELECT * FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ?
        ORDER BY id
    ");
    $stmt->execute([$userId, $today]);
    $todayTasksBefore = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($todayTasksBefore) . " existing tasks for today</p>";
    
    if (!empty($todayTasksBefore)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Rollover Source</th><th>Source Field</th></tr>";
        foreach ($todayTasksBefore as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['rollover_source_date'] ?? 'N/A') . "</td>";
            echo "<td>" . ($task['source_field'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Run rollover manually
    echo "<div class='section'>";
    echo "<h3>Step 3: Running Rollover Process</h3>";
    $rolledCount = $planner->rolloverUncompletedTasks();
    echo "<p class='success'><strong>Rollover Result: {$rolledCount} tasks rolled over</strong></p>";
    echo "</div>";
    
    // Check tasks for today after rollover
    echo "<div class='section'>";
    echo "<h3>Step 4: Check Tasks for Today (After Rollover)</h3>";
    $stmt = $db->prepare("
        SELECT * FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ?
        ORDER BY id
    ");
    $stmt->execute([$userId, $today]);
    $todayTasksAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($todayTasksAfter) . " tasks for today after rollover</p>";
    
    if (!empty($todayTasksAfter)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Progress</th><th>Rollover Source</th><th>Source Field</th><th>Created At</th></tr>";
        foreach ($todayTasksAfter as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>" . ($task['rollover_source_date'] ?? 'N/A') . "</td>";
            echo "<td>" . ($task['source_field'] ?? 'N/A') . "</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Test the getTasksForDate method
    echo "<div class='section'>";
    echo "<h3>Step 5: Test getTasksForDate Method</h3>";
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    echo "<p>getTasksForDate returned " . count($plannedTasks) . " tasks</p>";
    
    if (!empty($plannedTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Progress</th><th>Task Indicator</th></tr>";
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>" . ($task['task_indicator'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check if there are any tasks in the main tasks table that should be fetched
    echo "<div class='section'>";
    echo "<h3>Step 6: Check Tasks Table for Today</h3>";
    $stmt = $db->prepare("
        SELECT 
            t.id, t.title, t.status, t.assigned_to, t.planned_date, t.deadline, t.created_at, t.updated_at,
            CASE 
                WHEN DATE(t.planned_date) = ? THEN 'planned_date'
                WHEN DATE(t.deadline) = ? THEN 'deadline'
                WHEN DATE(t.created_at) = ? THEN 'created_at'
                WHEN DATE(t.updated_at) = ? THEN 'updated_at'
                ELSE 'other'
            END as source_field
        FROM tasks t
        WHERE (t.assigned_to = ? OR t.assigned_by = ?) 
        AND (
            DATE(t.planned_date) = ? OR
            DATE(t.deadline) = ? OR
            DATE(t.created_at) = ? OR
            DATE(t.updated_at) = ?
        )
        AND t.status != 'completed'
    ");
    $stmt->execute([$today, $today, $today, $today, $userId, $userId, $today, $today, $today, $today]);
    $relevantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($relevantTasks) . " relevant tasks in tasks table for today</p>";
    
    if (!empty($relevantTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Planned Date</th><th>Deadline</th><th>Source Field</th></tr>";
        foreach ($relevantTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['planned_date'] ?? 'N/A') . "</td>";
            echo "<td>" . ($task['deadline'] ?? 'N/A') . "</td>";
            echo "<td>{$task['source_field']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='/ergon/workflow/daily-planner'>Go to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
</body>
</html>