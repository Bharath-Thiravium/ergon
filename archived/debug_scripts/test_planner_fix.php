<?php
/**
 * Test file to verify the Planner module task fetching fix
 * 
 * This file tests that tasks are properly fetched and displayed for today's date
 * including tasks with planned_date = today, deadline = today, created today, etc.
 */

session_start();

// Mock session for testing (replace with actual user ID)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Replace with actual user ID for testing
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h1>Planner Module Task Fetching Test</h1>";
    echo "<p><strong>Testing Date:</strong> {$today}</p>";
    echo "<p><strong>User ID:</strong> {$userId}</p>";
    echo "<hr>";
    
    // Test 1: Check tasks in the tasks table
    echo "<h2>1. Tasks in Tasks Table (for today)</h2>";
    $stmt = $db->prepare("
        SELECT 
            t.id, t.title, t.planned_date, t.deadline, t.created_at, t.status,
            CASE 
                WHEN t.planned_date = ? THEN 'planned_date'
                WHEN DATE(t.deadline) = ? THEN 'deadline'
                WHEN DATE(t.created_at) = ? THEN 'created_date'
                WHEN t.status = 'in_progress' THEN 'in_progress'
                ELSE 'other'
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
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$today, $today, $today, $userId, $today, $today, $today]);
    $tasksFromTable = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tasksFromTable)) {
        echo "<p style='color: orange;'>⚠️ No tasks found in tasks table for today. This might be the issue!</p>";
        
        // Check if there are any tasks at all for this user
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        $totalTasks = $stmt->fetchColumn();
        echo "<p>Total tasks for user: {$totalTasks}</p>";
        
        if ($totalTasks > 0) {
            echo "<h3>All tasks for this user:</h3>";
            $stmt = $db->prepare("SELECT id, title, planned_date, deadline, created_at, status FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Planned Date</th><th>Deadline</th><th>Created</th><th>Status</th></tr>";
            foreach ($allTasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>" . ($task['planned_date'] ?: 'NULL') . "</td>";
                echo "<td>" . ($task['deadline'] ?: 'NULL') . "</td>";
                echo "<td>" . date('Y-m-d', strtotime($task['created_at'])) . "</td>";
                echo "<td>{$task['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: green;'>✅ Found " . count($tasksFromTable) . " tasks in tasks table</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Source</th><th>Planned Date</th><th>Deadline</th><th>Status</th></tr>";
        foreach ($tasksFromTable as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td><strong>{$task['source_field']}</strong></td>";
            echo "<td>" . ($task['planned_date'] ?: 'NULL') . "</td>";
            echo "<td>" . ($task['deadline'] ?: 'NULL') . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // Test 2: Check daily_tasks table before fetch
    echo "<h2>2. Daily Tasks Table (before fetch)</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasksCountBefore = $stmt->fetchColumn();
    echo "<p>Daily tasks count before fetch: {$dailyTasksCountBefore}</p>";
    
    // Test 3: Run fetchAssignedTasksForDate
    echo "<h2>3. Running fetchAssignedTasksForDate</h2>";
    $addedCount = $planner->fetchAssignedTasksForDate($userId, $today);
    echo "<p style='color: blue;'>📥 Added {$addedCount} new tasks to daily_tasks table</p>";
    
    // Test 4: Check daily_tasks table after fetch
    echo "<h2>4. Daily Tasks Table (after fetch)</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasksCountAfter = $stmt->fetchColumn();
    echo "<p>Daily tasks count after fetch: {$dailyTasksCountAfter}</p>";
    
    // Test 5: Get tasks using getTasksForDate (the main method)
    echo "<h2>5. Final Result: getTasksForDate</h2>";
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    
    if (empty($plannedTasks)) {
        echo "<p style='color: red;'>❌ ISSUE CONFIRMED: getTasksForDate returned no tasks!</p>";
        
        // Debug: Check what's in daily_tasks table
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyTasksDebug = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($dailyTasksDebug)) {
            echo "<p style='color: red;'>❌ No entries in daily_tasks table for today</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Found " . count($dailyTasksDebug) . " entries in daily_tasks table, but getTasksForDate returned none</p>";
            echo "<h4>Daily tasks entries:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Task ID</th><th>Title</th><th>Status</th><th>Source Field</th></tr>";
            foreach ($dailyTasksDebug as $dt) {
                echo "<tr>";
                echo "<td>{$dt['id']}</td>";
                echo "<td>" . ($dt['task_id'] ?: 'NULL') . "</td>";
                echo "<td>{$dt['title']}</td>";
                echo "<td>{$dt['status']}</td>";
                echo "<td>" . ($dt['source_field'] ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: green;'>✅ SUCCESS: getTasksForDate returned " . count($plannedTasks) . " tasks!</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Source</th><th>SLA Hours</th></tr>";
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>" . ($task['source_field'] ?: 'N/A') . "</td>";
            echo "<td>" . ($task['sla_hours'] ?: '0.25') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>6. Test Summary</h2>";
    
    if (count($plannedTasks) > 0) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ FIX SUCCESSFUL!</p>";
        echo "<p>The Planner module is now correctly fetching and displaying " . count($plannedTasks) . " tasks for today's date.</p>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ ISSUE STILL EXISTS</p>";
        echo "<p>The Planner module is still not fetching tasks properly. Further investigation needed.</p>";
        
        if (count($tasksFromTable) > 0) {
            echo "<p><strong>Recommendation:</strong> There are tasks in the tasks table that should appear today, but they're not being processed correctly by the DailyPlanner model.</p>";
        } else {
            echo "<p><strong>Recommendation:</strong> Create some test tasks with today's planned_date or deadline to test the functionality.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
</style>