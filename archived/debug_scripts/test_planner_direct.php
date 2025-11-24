<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

try {
    $planner = new DailyPlanner();
    $userId = 1;
    $today = date('Y-m-d');
    
    echo "<h1>Direct Planner Test</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}</style>";
    
    echo "<h2>Testing getTasksForDate directly</h2>";
    
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $tasks = $planner->getTasksForDate($userId, $today);
    
    echo "<p class='info'>Tasks returned: " . count($tasks) . "</p>";
    
    if (!empty($tasks)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>SLA Hours</th><th>Task Indicator</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['sla_hours']}</td>";
            echo "<td>" . htmlspecialchars($task['task_indicator'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No tasks returned from getTasksForDate</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>