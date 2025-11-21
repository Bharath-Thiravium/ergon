<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h2>Testing Task Rollover</h2>";

try {
    $planner = new DailyPlanner();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "<h3>Current Status</h3>";
    echo "<p>User ID: {$userId}</p>";
    echo "<p>Today: {$today}</p>";
    echo "<p>Yesterday: {$yesterday}</p>";
    
    // Check for uncompleted tasks from previous days
    $db = Database::connect();
    $stmt = $db->prepare("
        SELECT * FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date < ? 
        AND status NOT IN ('completed', 'postponed') 
        AND completed_percentage < 100
        ORDER BY scheduled_date DESC
    ");
    $stmt->execute([$userId, $today]);
    $uncompletedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Uncompleted Tasks from Previous Days</h3>";
    if (empty($uncompletedTasks)) {
        echo "<p>No uncompleted tasks found from previous days.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Date</th><th>Status</th><th>Progress</th></tr>";
        foreach ($uncompletedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['scheduled_date']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Run rollover
    echo "<h3>Running Rollover...</h3>";
    $rolledCount = $planner->rolloverUncompletedTasks();
    echo "<p><strong>Rolled over {$rolledCount} tasks</strong></p>";
    
    // Check today's tasks after rollover
    $todayTasks = $planner->getTasksForDate($userId, $today);
    
    echo "<h3>Today's Tasks After Rollover</h3>";
    if (empty($todayTasks)) {
        echo "<p>No tasks found for today.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Progress</th><th>Rolled From</th></tr>";
        foreach ($todayTasks as $task) {
            $rolledFrom = $task['postponed_from_date'] ? $task['postponed_from_date'] : 'N/A';
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>{$rolledFrom}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='/ergon/workflow/daily-planner'>Go to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>