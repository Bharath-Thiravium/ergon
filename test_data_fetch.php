<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Data Fetch Test</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    echo "<h3>1. Testing DailyPlanner Model</h3>";
    $tasks = $planner->getTasksForDate($userId, $today);
    
    if (!empty($tasks)) {
        echo "<p class='success'>✅ Found " . count($tasks) . " tasks</p>";
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>SLA Hours</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['sla_hours']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No tasks found from model</p>";
    }
    
    echo "<h3>2. Testing Direct Database Query</h3>";
    $stmt = $db->prepare("SELECT id, title, status FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $directTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($directTasks)) {
        echo "<p class='success'>✅ Found " . count($directTasks) . " tasks in database</p>";
        echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th></tr>";
        foreach ($directTasks as $task) {
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No tasks found in database</p>";
    }
    
    echo "<h3>3. Testing Stats Calculation</h3>";
    $stats = $planner->getDailyStats($userId, $today);
    echo "<p>Total Tasks: {$stats['total_tasks']}</p>";
    echo "<p>Completed: {$stats['completed_tasks']}</p>";
    echo "<p>In Progress: {$stats['in_progress_tasks']}</p>";
    
    echo "<p class='success'>✅ Data fetching operations working correctly!</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='/ergon/workflow/daily-planner'>Test Daily Planner</a></p>";
?>