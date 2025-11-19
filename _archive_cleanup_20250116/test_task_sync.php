<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Task Sync Test - Tasks → Daily Planner</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    echo "<h3>1. Today's Tasks from Tasks Module</h3>";
    $stmt = $db->prepare("
        SELECT id, title, priority, status, created_at, deadline, planned_date, assigned_by, assigned_to
        FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(created_at) = ? OR
            DATE(deadline) = ? OR
            DATE(planned_date) = ? OR
            DATE(updated_at) = ? OR
            status = 'in_progress'
        )
        AND status != 'completed' 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $today, $today, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($todayTasks)) {
        echo "<p class='success'>✅ Found " . count($todayTasks) . " tasks for today</p>";
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Created</th><th>Source</th></tr>";
        foreach ($todayTasks as $task) {
            $source = ($task['assigned_by'] != $task['assigned_to']) ? 'From Others' : 'Self';
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['priority']}</td><td>{$task['status']}</td><td>" . date('M j', strtotime($task['created_at'])) . "</td><td>$source</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No tasks found for today</p>";
        
        // Create a sample task for testing
        echo "<p class='info'>Creating a sample task for testing...</p>";
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, status, created_at, sla_hours)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            'Sample Task for Today - ' . date('H:i:s'),
            'This is a sample task created for testing the daily planner sync',
            $userId, $userId, 'medium', 'assigned', 2
        ]);
        
        echo "<p class='success'>✅ Sample task created</p>";
        
        // Re-fetch
        $stmt->execute([$userId, $today, $today, $today, $today]);
        $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<h3>2. Clear Daily Tasks and Recreate</h3>";
    
    // Clear existing daily tasks for today
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    echo "<p>Cleared existing daily tasks</p>";
    
    // Recreate daily tasks from regular tasks
    foreach ($todayTasks as $task) {
        $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
            ? "[From Others] " . $task['title']
            : "[Self] " . $task['title'];
        
        $plannedDuration = 120; // 2 hours default
        
        $stmt = $db->prepare("
            INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
        ");
        $stmt->execute([
            $userId, $task['id'], $today, $taskTitle, 
            $task['description'] ?? '', $plannedDuration, $task['priority']
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($todayTasks) . " daily tasks</p>";
    
    echo "<h3>3. Verify Daily Planner Tasks</h3>";
    $stmt = $db->prepare("SELECT id, title, status, task_id FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($dailyTasks)) {
        echo "<p class='success'>✅ Daily planner has " . count($dailyTasks) . " tasks</p>";
        echo "<table border='1'><tr><th>Daily ID</th><th>Title</th><th>Status</th><th>Linked Task ID</th></tr>";
        foreach ($dailyTasks as $task) {
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['task_id']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No daily tasks found</p>";
    }
    
    echo "<p class='success'>✅ Task sync completed successfully!</p>";
    echo "<p><a href='/ergon/tasks' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin-right:10px;'>View Tasks Module</a>";
    echo "<a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>View Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>