<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h2>Testing Rollover Fix</h2>";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "<h3>Creating Test Data</h3>";
    
    // First, let's create some test uncompleted tasks for yesterday
    $testTasks = [
        ['title' => 'Test Task 1 - Not Started', 'status' => 'not_started', 'progress' => 0],
        ['title' => 'Test Task 2 - In Progress', 'status' => 'in_progress', 'progress' => 50],
        ['title' => 'Test Task 3 - Postponed', 'status' => 'postponed', 'progress' => 25]
    ];
    
    // Clean up any existing test data
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND title LIKE 'Test Task%'");
    $stmt->execute([$userId]);
    
    // Insert test tasks for yesterday
    foreach ($testTasks as $task) {
        $stmt = $db->prepare("
            INSERT INTO daily_tasks 
            (user_id, title, scheduled_date, status, completed_percentage, priority, planned_duration, created_at)
            VALUES (?, ?, ?, ?, ?, 'medium', 60, NOW())
        ");
        $stmt->execute([$userId, $task['title'], $yesterday, $task['status'], $task['progress']]);
        echo "<p>Created: {$task['title']} for {$yesterday}</p>";
    }
    
    echo "<h3>Before Rollover</h3>";
    
    // Check tasks for today before rollover
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $todayCountBefore = $stmt->fetchColumn();
    echo "<p>Tasks for today before rollover: {$todayCountBefore}</p>";
    
    // Check uncompleted tasks from yesterday
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE scheduled_date = ? 
        AND status IN ('not_started', 'in_progress', 'postponed') 
        AND completed_percentage < 100
    ");
    $stmt->execute([$yesterday]);
    $yesterdayUncompleted = $stmt->fetchColumn();
    echo "<p>Uncompleted tasks from yesterday: {$yesterdayUncompleted}</p>";
    
    echo "<h3>Running Rollover</h3>";
    
    // Run the rollover
    $rolledCount = $planner->rolloverUncompletedTasks();
    echo "<p><strong>Rollover completed: {$rolledCount} tasks rolled over</strong></p>";
    
    echo "<h3>After Rollover</h3>";
    
    // Check tasks for today after rollover
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $todayCountAfter = $stmt->fetchColumn();
    echo "<p>Tasks for today after rollover: {$todayCountAfter}</p>";
    
    // Show the rolled over tasks
    $stmt = $db->prepare("
        SELECT title, status, completed_percentage, rollover_source_date, source_field 
        FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? AND rollover_source_date IS NOT NULL
    ");
    $stmt->execute([$userId, $today]);
    $rolledTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rolledTasks)) {
        echo "<h4>Rolled Over Tasks:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Title</th><th>Status</th><th>Progress</th><th>Rolled From</th><th>Source Field</th></tr>";
        foreach ($rolledTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>{$task['rollover_source_date']}</td>";
            echo "<td>{$task['source_field']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Testing getTasksForDate Method</h3>";
    
    // Test the getTasksForDate method
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    echo "<p>getTasksForDate returned " . count($plannedTasks) . " tasks</p>";
    
    if (!empty($plannedTasks)) {
        echo "<h4>Tasks from getTasksForDate:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Title</th><th>Status</th><th>Progress</th><th>Task Indicator</th></tr>";
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['completed_percentage']}%</td>";
            echo "<td>" . ($task['task_indicator'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><strong>Test Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Created {$yesterdayUncompleted} test tasks for yesterday</li>";
    echo "<li>Rollover processed {$rolledCount} tasks</li>";
    echo "<li>Today now has {$todayCountAfter} tasks (was {$todayCountBefore})</li>";
    echo "<li>getTasksForDate returned " . count($plannedTasks) . " tasks</li>";
    echo "</ul>";
    
    if ($rolledCount > 0) {
        echo "<p style='color: green;'><strong>✅ Rollover is working correctly!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Rollover may not be working as expected.</strong></p>";
    }
    
    echo "<p><a href='/ergon/workflow/daily-planner'>Go to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>