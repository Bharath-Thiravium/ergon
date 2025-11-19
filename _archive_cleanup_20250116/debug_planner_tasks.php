<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Simulate user session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to your user ID
    $_SESSION['role'] = 'admin';
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    
    echo "<h2>Debug: Planner Tasks Issue</h2>";
    echo "<p>User ID: $userId</p>";
    
    // 1. Check all tasks for this user
    echo "<h3>1. All Tasks for User $userId:</h3>";
    $stmt = $db->prepare("SELECT id, title, assigned_by, assigned_to, status, created_at FROM tasks WHERE assigned_to = ? OR assigned_by = ?");
    $stmt->execute([$userId, $userId]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allTasks)) {
        echo "<p style='color: red;'>❌ No tasks found for user $userId</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Assigned By</th><th>Assigned To</th><th>Status</th><th>Created</th></tr>";
        foreach ($allTasks as $task) {
            $color = ($task['assigned_by'] == $userId && $task['assigned_to'] == $userId) ? 'background: yellow;' : '';
            echo "<tr style='$color'>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['assigned_by']}</td>";
            echo "<td>{$task['assigned_to']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Check non-completed tasks
    echo "<h3>2. Non-Completed Tasks:</h3>";
    $stmt = $db->prepare("SELECT id, title, assigned_by, assigned_to, status FROM tasks WHERE (assigned_to = ? OR (assigned_by = ? AND assigned_to = ?)) AND status != 'completed'");
    $stmt->execute([$userId, $userId, $userId]);
    $nonCompletedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($nonCompletedTasks)) {
        echo "<p style='color: red;'>❌ No non-completed tasks found</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($nonCompletedTasks) . " non-completed tasks</p>";
        foreach ($nonCompletedTasks as $task) {
            echo "<p>- Task {$task['id']}: {$task['title']} (Status: {$task['status']})</p>";
        }
    }
    
    // 3. Check daily_tasks table
    echo "<h3>3. Daily Tasks Table:</h3>";
    $today = date('Y-m-d');
    try {
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($dailyTasks)) {
            echo "<p style='color: orange;'>⚠ No daily tasks found for today ($today)</p>";
        } else {
            echo "<p style='color: green;'>✅ Found " . count($dailyTasks) . " daily tasks for today</p>";
            foreach ($dailyTasks as $task) {
                echo "<p>- Daily Task {$task['id']}: {$task['title']} (Status: {$task['status']})</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ daily_tasks table error: " . $e->getMessage() . "</p>";
    }
    
    // 4. Test the exact query from UnifiedWorkflowController
    echo "<h3>4. Testing Controller Query:</h3>";
    try {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE (assigned_to = ? OR (assigned_by = ? AND assigned_to = ?)) AND status != 'completed' ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId, $userId, $userId]);
        $controllerTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($controllerTasks)) {
            echo "<p style='color: red;'>❌ Controller query returned no tasks</p>";
        } else {
            echo "<p style='color: green;'>✅ Controller query found " . count($controllerTasks) . " tasks</p>";
            foreach ($controllerTasks as $task) {
                echo "<p>- Task {$task['id']}: {$task['title']} (Assigned by: {$task['assigned_by']}, Assigned to: {$task['assigned_to']})</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Controller query error: " . $e->getMessage() . "</p>";
    }
    
    // 5. Check if tasks table has the right structure
    echo "<h3>5. Tasks Table Structure:</h3>";
    $stmt = $db->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'title', 'assigned_by', 'assigned_to', 'status'];
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] == $col) {
                echo "<p style='color: green;'>✅ Column '$col' exists</p>";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
        }
    }
    
    echo "<h3>6. Recommendation:</h3>";
    if (empty($allTasks)) {
        echo "<p style='color: red;'>Create some tasks first, then test the planner.</p>";
    } elseif (empty($nonCompletedTasks)) {
        echo "<p style='color: orange;'>All tasks are completed. Create new tasks or change status of existing tasks.</p>";
    } else {
        echo "<p style='color: green;'>Tasks exist and should appear in planner. Check the daily planner page.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>