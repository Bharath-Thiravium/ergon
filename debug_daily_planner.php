<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Simulate user session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to your user ID
    $_SESSION['role'] = 'owner';
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h2>Daily Planner Debug Information</h2>";
    echo "<p>User ID: <strong>$userId</strong></p>";
    echo "<p>Today's Date: <strong>$today</strong></p>";
    echo "<hr>";
    
    // 1. Check all tasks for this user
    echo "<h3>1. All Tasks for User $userId:</h3>";
    $stmt = $db->prepare("
        SELECT id, title, assigned_by, assigned_to, status, priority, created_at,
               CASE 
                   WHEN assigned_by != assigned_to THEN 'From Others' 
                   ELSE 'Self-Assigned' 
               END as task_source
        FROM tasks 
        WHERE assigned_to = ? OR (assigned_by = ? AND assigned_to = ?)
        ORDER BY 
            CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
            created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allTasks)) {
        echo "<p style='color: red;'>❌ No tasks found for user $userId</p>";
        echo "<p><a href='add_sample_tasks.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Add Sample Tasks</a></p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Title</th><th>Source</th><th>Priority</th><th>Status</th><th>Created</th></tr>";
        foreach ($allTasks as $task) {
            $rowColor = ($task['task_source'] === 'From Others') ? 'background: #fff3cd;' : 'background: #d1ecf1;';
            echo "<tr style='$rowColor'>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td><strong>{$task['task_source']}</strong></td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . date('M j, Y', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count breakdown
        $fromOthers = count(array_filter($allTasks, fn($t) => $t['task_source'] === 'From Others'));
        $selfAssigned = count(array_filter($allTasks, fn($t) => $t['task_source'] === 'Self-Assigned'));
        
        echo "<p><strong>Summary:</strong> $fromOthers from others, $selfAssigned self-assigned</p>";
    }
    
    echo "<hr>";
    
    // 2. Check daily_tasks table
    echo "<h3>2. Daily Tasks for Today ($today):</h3>";
    try {
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($dailyTasks)) {
            echo "<p style='color: orange;'>⚠ No daily tasks found for today</p>";
            echo "<p><em>Daily tasks will be auto-created from regular tasks when you visit the planner</em></p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Progress</th></tr>";
            foreach ($dailyTasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['priority']}</td>";
                echo "<td>{$task['completed_percentage']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ daily_tasks table error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 3. Test the controller query
    echo "<h3>3. Testing Controller Query (Non-completed tasks):</h3>";
    try {
        $stmt = $db->prepare("
            SELECT * FROM tasks 
            WHERE (assigned_to = ? OR (assigned_by = ? AND assigned_to = ?)) 
            AND status != 'completed' 
            ORDER BY 
                CASE 
                    WHEN assigned_by != assigned_to THEN 1  -- Tasks from others (higher priority)
                    ELSE 2                                   -- Self-assigned tasks
                END,
                created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId, $userId, $userId]);
        $controllerTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($controllerTasks)) {
            echo "<p style='color: red;'>❌ No non-completed tasks found</p>";
        } else {
            echo "<p style='color: green;'>✅ Found " . count($controllerTasks) . " non-completed tasks</p>";
            foreach ($controllerTasks as $task) {
                $source = ($task['assigned_by'] != $task['assigned_to']) ? 'From Others' : 'Self-Assigned';
                echo "<p>- <strong>{$task['title']}</strong> ({$source}, Priority: {$task['priority']})</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Controller query error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 4. Quick actions
    echo "<h3>4. Quick Actions:</h3>";
    echo "<p>";
    echo "<a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>View Daily Planner</a>";
    echo "<a href='add_sample_tasks.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Add Sample Tasks</a>";
    echo "<a href='/ergon/tasks/create' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Create New Task</a>";
    echo "</p>";
    
    // 5. Clear daily tasks (for testing)
    if (isset($_GET['clear_daily'])) {
        try {
            $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
            $stmt->execute([$userId, $today]);
            echo "<p style='color: green;'>✅ Cleared daily tasks for today. <a href='?'>Refresh</a></p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error clearing daily tasks: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><a href='?clear_daily=1' style='background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;'>Clear Today's Daily Tasks (for testing)</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; }
th { font-weight: bold; }
</style>