<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h2>Rollover System Validation</h2>";

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "<h3>System Status</h3>";
    echo "<p>Current Date: {$today}</p>";
    echo "<p>Yesterday: {$yesterday}</p>";
    echo "<p>User ID: {$userId}</p>";
    
    // Check if daily_tasks table exists and has required columns
    echo "<h3>Database Schema Validation</h3>";
    
    $stmt = $db->prepare("SHOW TABLES LIKE 'daily_tasks'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p>✅ daily_tasks table exists</p>";
        
        // Check for required columns
        $requiredColumns = [
            'rollover_source_date',
            'rollover_timestamp', 
            'source_field',
            'original_task_id',
            'pause_duration'
        ];
        
        foreach ($requiredColumns as $column) {
            $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE '{$column}'");
            $stmt->execute();
            if ($stmt->fetch()) {
                echo "<p>✅ Column '{$column}' exists</p>";
            } else {
                echo "<p>❌ Column '{$column}' missing</p>";
            }
        }
    } else {
        echo "<p>❌ daily_tasks table does not exist</p>";
    }
    
    // Check current data state
    echo "<h3>Current Data State</h3>";
    
    // Tasks for today
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $todayCount = $stmt->fetchColumn();
    echo "<p>Tasks for today: {$todayCount}</p>";
    
    // Tasks from yesterday
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $yesterday]);
    $yesterdayCount = $stmt->fetchColumn();
    echo "<p>Tasks from yesterday: {$yesterdayCount}</p>";
    
    // Uncompleted tasks from yesterday
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? 
        AND status IN ('not_started', 'in_progress', 'postponed') 
        AND completed_percentage < 100
    ");
    $stmt->execute([$userId, $yesterday]);
    $yesterdayUncompleted = $stmt->fetchColumn();
    echo "<p>Uncompleted tasks from yesterday: {$yesterdayUncompleted}</p>";
    
    // Already rolled over tasks
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ? AND rollover_source_date = ?
    ");
    $stmt->execute([$userId, $today, $yesterday]);
    $alreadyRolled = $stmt->fetchColumn();
    echo "<p>Already rolled over from yesterday: {$alreadyRolled}</p>";
    
    // Check tasks table for today
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(planned_date) = ? OR
            DATE(deadline) = ? OR
            DATE(created_at) = ? OR
            DATE(updated_at) = ?
        )
        AND status != 'completed'
    ");
    $stmt->execute([$userId, $today, $today, $today, $today]);
    $tasksTableCount = $stmt->fetchColumn();
    echo "<p>Relevant tasks in tasks table for today: {$tasksTableCount}</p>";
    
    // Test rollover method
    echo "<h3>Rollover Method Test</h3>";
    
    if ($yesterdayUncompleted > 0 && $alreadyRolled == 0) {
        echo "<p>🔄 There are uncompleted tasks that can be rolled over</p>";
        echo "<p><a href='test_rollover_fix.php' style='background: #007cba; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Run Rollover Test</a></p>";
    } elseif ($alreadyRolled > 0) {
        echo "<p>✅ Tasks have already been rolled over from yesterday</p>";
    } else {
        echo "<p>ℹ️ No uncompleted tasks from yesterday to roll over</p>";
    }
    
    // Test getTasksForDate method
    echo "<h3>getTasksForDate Method Test</h3>";
    
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    echo "<p>getTasksForDate returned: " . count($plannedTasks) . " tasks</p>";
    
    if (!empty($plannedTasks)) {
        $rolledOverTasks = array_filter($plannedTasks, function($task) {
            return !empty($task['rollover_source_date']);
        });
        echo "<p>Rolled over tasks in result: " . count($rolledOverTasks) . "</p>";
        
        if (!empty($rolledOverTasks)) {
            echo "<h4>Rolled Over Tasks:</h4>";
            echo "<ul>";
            foreach ($rolledOverTasks as $task) {
                echo "<li>{$task['title']} (from {$task['rollover_source_date']})</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Recommendations</h3>";
    
    if ($yesterdayUncompleted > 0 && $alreadyRolled == 0) {
        echo "<p>🔧 Run the rollover process to bring forward uncompleted tasks</p>";
    }
    
    if (count($plannedTasks) == 0) {
        echo "<p>🔧 No tasks found for today. Check if tasks are being created in the tasks table with today's date</p>";
    }
    
    echo "<p><a href='/ergon/workflow/daily-planner'>Go to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>