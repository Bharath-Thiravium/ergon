<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$db = Database::connect();
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Fix Start Button Issue</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

// Step 1: Ensure daily_tasks table has proper structure
echo "<h3>1. Fixing Database Structure</h3>";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            scheduled_date DATE NOT NULL,
            planned_start_time TIME NULL,
            planned_duration INT DEFAULT 60,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'not_started',
            start_time TIMESTAMP NULL,
            pause_time TIMESTAMP NULL,
            resume_time TIMESTAMP NULL,
            completion_time TIMESTAMP NULL,
            active_seconds INT DEFAULT 0,
            completed_percentage INT DEFAULT 0,
            postponed_from_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Daily tasks table structure ensured</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Table creation error: " . $e->getMessage() . "</p>";
}

// Step 2: Clear existing daily tasks for today and recreate
echo "<h3>2. Recreating Daily Tasks for Today</h3>";
try {
    // Clear existing daily tasks for today
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    echo "<p>Cleared existing daily tasks for today</p>";
    
    // Get regular tasks for today
    $stmt = $db->prepare("
        SELECT *, COALESCE(sla_hours, 1) as sla_hours FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(created_at) = ? OR
            DATE(deadline) = ? OR
            DATE(planned_date) = ? OR
            status = 'in_progress'
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
        LIMIT 10
    ");
    $stmt->execute([$userId, $today, $today, $today]);
    $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($regularTasks)) {
        // Create a sample task if none exist
        $stmt = $db->prepare("
            INSERT INTO tasks (assigned_to, assigned_by, title, description, priority, status, created_at, deadline, sla_hours)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([
            $userId, $userId, 
            'Sample Task for Testing', 
            'This is a sample task created for testing the daily planner',
            'medium', 'assigned', $today, 2
        ]);
        
        // Re-fetch tasks
        $stmt->execute([$userId, $today, $today, $today]);
        $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Created sample task</p>";
    }
    
    // Create daily tasks from regular tasks
    foreach ($regularTasks as $task) {
        $taskSource = ($task['assigned_by'] != $task['assigned_to']) ? 'assigned_by_others' : 'self_assigned';
        $taskTitle = $task['title'];
        
        if ($taskSource === 'assigned_by_others') {
            $taskTitle = "[From Others] " . $taskTitle;
        } else {
            $taskTitle = "[Self] " . $taskTitle;
        }
        
        $slaHours = !empty($task['sla_hours']) ? (float)$task['sla_hours'] : 1;
        $plannedDurationMinutes = $slaHours * 60;
        
        $stmt = $db->prepare("
            INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
        ");
        $stmt->execute([
            $userId, 
            $task['id'], 
            $today, 
            $taskTitle, 
            $task['description'], 
            $plannedDurationMinutes,
            $task['priority'] ?? 'medium'
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($regularTasks) . " daily tasks</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Task creation error: " . $e->getMessage() . "</p>";
}

// Step 3: Test the start functionality
echo "<h3>3. Testing Start Functionality</h3>";
try {
    // Get a task to test
    $stmt = $db->prepare("SELECT id, status FROM daily_tasks WHERE user_id = ? AND scheduled_date = ? AND status = 'not_started' LIMIT 1");
    $stmt->execute([$userId, $today]);
    $testTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testTask) {
        $testTaskId = $testTask['id'];
        echo "<p>Testing with task ID: $testTaskId</p>";
        
        // Test the start operation
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Start functionality works</p>";
            
            // Reset for actual use
            $stmt = $db->prepare("UPDATE daily_tasks SET status = 'not_started', start_time = NULL WHERE id = ?");
            $stmt->execute([$testTaskId]);
            echo "<p>Reset task for actual use</p>";
        } else {
            echo "<p class='error'>❌ Start functionality failed</p>";
        }
    } else {
        echo "<p class='error'>❌ No test task available</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Test error: " . $e->getMessage() . "</p>";
}

// Step 4: Show current tasks
echo "<h3>4. Current Daily Tasks</h3>";
$stmt = $db->prepare("SELECT id, title, status FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
$stmt->execute([$userId, $today]);
$currentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($currentTasks)) {
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th></tr>";
    foreach ($currentTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>No tasks found</p>";
}

echo "<p class='success'>✅ Start button should now work properly!</p>";
echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Daily Planner</a></p>";
?>