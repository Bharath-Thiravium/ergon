<?php
// Debug script to check postpone issue
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Debug Postpone Issue</h2>";
    
    // Check for tasks with postponed_to_date values
    $stmt = $db->prepare("
        SELECT id, title, scheduled_date, status, postponed_to_date, postponed_from_date 
        FROM daily_tasks 
        WHERE postponed_to_date IS NOT NULL 
        ORDER BY scheduled_date DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $postponedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tasks with postponed_to_date:</h3>";
    if ($postponedTasks) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Scheduled Date</th><th>Status</th><th>Postponed To</th><th>Postponed From</th></tr>";
        foreach ($postponedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['scheduled_date']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['postponed_to_date']}</td>";
            echo "<td>{$task['postponed_from_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No tasks with postponed_to_date found.</p>";
    }
    
    // Check for recent daily tasks
    $stmt = $db->prepare("
        SELECT id, title, scheduled_date, status, postponed_to_date, postponed_from_date 
        FROM daily_tasks 
        WHERE scheduled_date >= CURDATE() - INTERVAL 3 DAY
        ORDER BY scheduled_date DESC, id DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Daily Tasks:</h3>";
    if ($recentTasks) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Scheduled Date</th><th>Status</th><th>Postponed To</th><th>Postponed From</th></tr>";
        foreach ($recentTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['scheduled_date']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['postponed_to_date']}</td>";
            echo "<td>{$task['postponed_from_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent tasks found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>