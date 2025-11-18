<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h2>Daily Planner Debug - Root Cause Analysis</h2>";
    echo "<p>User ID: <strong>$userId</strong> | Date: <strong>$today</strong></p>";
    echo "<hr>";
    
    // 1. Check if tables exist
    echo "<h3>1. Database Structure Check</h3>";
    
    $tables = ['tasks', 'daily_tasks', 'users'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Table <strong>$table</strong> exists</p>";
                
                if ($table === 'tasks') {
                    $stmt = $db->query("DESCRIBE tasks");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo "<small>Columns: " . implode(', ', $columns) . "</small><br>";
                }
            } else {
                echo "<p>❌ Table <strong>$table</strong> missing</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking table $table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    
    // 2. Check current tasks
    echo "<h3>2. Current Tasks Analysis</h3>";
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        $totalTasks = $stmt->fetchColumn();
        echo "<p>Total tasks for user: <strong>$totalTasks</strong></p>";
        
        if ($totalTasks > 0) {
            $stmt = $db->prepare("
                SELECT id, title, assigned_by, assigned_to, status, priority, 
                       DATE(created_at) as created_date, DATE(deadline) as deadline_date,
                       DATE(planned_date) as planned_date
                FROM tasks 
                WHERE assigned_to = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Deadline</th><th>Planned</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['created_date']}</td>";
                echo "<td>{$task['deadline_date']}</td>";
                echo "<td>{$task['planned_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking tasks: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 3. Test the exact controller query
    echo "<h3>3. Testing Controller Query</h3>";
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM tasks 
            WHERE assigned_to = ? 
            AND (
                DATE(created_at) = ? OR
                DATE(deadline) = ? OR
                DATE(planned_date) = ? OR
                status = 'in_progress' OR
                (assigned_by != assigned_to AND DATE(COALESCE(assigned_at, created_at)) = ?)
            )
            AND status != 'completed' 
            ORDER BY 
                CASE 
                    WHEN assigned_by != assigned_to THEN 1
                    ELSE 2
                END,
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                    ELSE 4
                END,
                created_at DESC 
            LIMIT 15
        ");
        $stmt->execute([$userId, $today, $today, $today, $today]);
        $controllerTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Controller query result: <strong>" . count($controllerTasks) . " tasks</strong></p>";
        
        if (empty($controllerTasks)) {
            echo "<p style='color: red;'>❌ No tasks found by controller query</p>";
            
            // Test individual conditions
            echo "<h4>Testing individual conditions:</h4>";
            
            $conditions = [
                "DATE(created_at) = '$today'" => "Created today",
                "DATE(deadline) = '$today'" => "Deadline today", 
                "DATE(planned_date) = '$today'" => "Planned today",
                "status = 'in_progress'" => "In progress",
                "assigned_by != assigned_to" => "Assigned by others"
            ];
            
            foreach ($conditions as $condition => $description) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND $condition AND status != 'completed'");
                $stmt->execute([$userId]);
                $count = $stmt->fetchColumn();
                echo "<p>- $description: <strong>$count</strong> tasks</p>";
            }
        } else {
            foreach ($controllerTasks as $task) {
                $source = ($task['assigned_by'] != $task['assigned_to']) ? 'From Others' : 'Self';
                echo "<p>✅ <strong>{$task['title']}</strong> ($source, {$task['priority']})</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Controller query error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 4. Check daily_tasks table
    echo "<h3>4. Daily Tasks Table</h3>";
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyCount = $stmt->fetchColumn();
        echo "<p>Daily tasks for today: <strong>$dailyCount</strong></p>";
        
        if ($dailyCount > 0) {
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
            $stmt->execute([$userId, $today]);
            $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Task ID</th></tr>";
            foreach ($dailyTasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['task_id']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Daily tasks error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 5. Quick fix actions
    echo "<h3>5. Quick Actions</h3>";
    
    if (isset($_GET['add_test_task'])) {
        try {
            // Get valid user IDs
            $stmt = $db->prepare("SELECT id FROM users ORDER BY id LIMIT 2");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $assignedBy = count($users) > 1 ? $users[1] : $users[0];
            
            $stmt = $db->prepare("
                INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, status, deadline, created_at, assigned_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                'Test Task - ' . date('H:i:s'),
                'This is a test task created for debugging',
                $assignedBy,
                $userId,
                'high',
                'assigned',
                $today
            ]);
            echo "<p style='color: green;'>✅ Added test task. <a href='?'>Refresh</a></p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding test task: " . $e->getMessage() . "</p>";
        }
    }
    
    if (isset($_GET['clear_daily'])) {
        try {
            $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ?");
            $stmt->execute([$userId]);
            echo "<p style='color: green;'>✅ Cleared daily tasks. <a href='?'>Refresh</a></p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error clearing daily tasks: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>";
    echo "<a href='?add_test_task=1' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Add Test Task</a>";
    echo "<a href='?clear_daily=1' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Clear Daily Tasks</a>";
    echo "<a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>View Planner</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; }
</style>