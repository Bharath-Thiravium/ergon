<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    $userId = 1;
    $today = date('Y-m-d');
    
    echo "<h1>Debug Planner Issue</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}</style>";
    
    echo "<h2>Step 1: Check Tasks Table</h2>";
    $stmt = $db->prepare("SELECT id, title, status, planned_date, deadline, created_at FROM tasks WHERE assigned_to = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>Planned Date</th><th>Deadline</th><th>Created</th></tr>";
    foreach ($tasks as $task) {
        echo "<tr>";
        echo "<td>{$task['id']}</td>";
        echo "<td>" . htmlspecialchars($task['title']) . "</td>";
        echo "<td>{$task['status']}</td>";
        echo "<td>{$task['planned_date']}</td>";
        echo "<td>{$task['deadline']}</td>";
        echo "<td>{$task['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Step 2: Test fetchAssignedTasksForDate</h2>";
    $addedCount = $planner->fetchAssignedTasksForDate($userId, $today);
    echo "<p class='info'>fetchAssignedTasksForDate returned: {$addedCount}</p>";
    
    echo "<h2>Step 3: Check Daily Tasks Table</h2>";
    $stmt = $db->prepare("SELECT id, title, status, scheduled_date, task_id, original_task_id, source_field FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th><th>Task ID</th><th>Original ID</th><th>Source</th></tr>";
    foreach ($dailyTasks as $task) {
        echo "<tr>";
        echo "<td>{$task['id']}</td>";
        echo "<td>" . htmlspecialchars($task['title']) . "</td>";
        echo "<td>{$task['status']}</td>";
        echo "<td>{$task['scheduled_date']}</td>";
        echo "<td>{$task['task_id']}</td>";
        echo "<td>{$task['original_task_id']}</td>";
        echo "<td>{$task['source_field']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Step 4: Test getTasksForDate</h2>";
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    echo "<p class='info'>getTasksForDate returned: " . count($plannedTasks) . " tasks</p>";
    
    if (!empty($plannedTasks)) {
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>SLA Hours</th></tr>";
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['sla_hours']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Step 5: Manual Query Test</h2>";
    $stmt = $db->prepare("
        SELECT 
            t.id, t.title, t.description, t.priority, t.status,
            t.deadline, t.estimated_duration, t.assigned_to, t.assigned_by,
            CASE 
                WHEN t.planned_date = ? THEN 'planned_date'
                WHEN DATE(t.deadline) = ? THEN 'deadline'
                WHEN DATE(t.created_at) = ? THEN 'created_date'
                WHEN t.status = 'in_progress' THEN 'in_progress'
                ELSE 'assigned'
            END as source_field
        FROM tasks t
        WHERE t.assigned_to = ? 
        AND t.status NOT IN ('completed', 'cancelled', 'deleted')
        AND (
            t.planned_date = ? OR
            DATE(t.deadline) = ? OR
            DATE(t.created_at) = ? OR
            t.status = 'in_progress' OR
            (t.planned_date IS NULL AND t.deadline IS NULL AND t.status IN ('assigned', 'not_started'))
        )
    ");
    $stmt->execute([$today, $today, $today, $userId, $today, $today, $today]);
    $manualTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Manual query found: " . count($manualTasks) . " tasks</p>";
    
    if (!empty($manualTasks)) {
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>Planned Date</th><th>Source Field</th></tr>";
        foreach ($manualTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['planned_date']}</td>";
            echo "<td>{$task['source_field']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>