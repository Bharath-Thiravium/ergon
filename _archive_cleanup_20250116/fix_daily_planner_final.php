<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Final Daily Planner Fix</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    echo "<h3>Step 1: Ensure Tasks Exist</h3>";
    
    // Check if user has any tasks
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ?");
    $stmt->execute([$userId]);
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount == 0) {
        echo "<p>No tasks found. Creating sample tasks...</p>";
        
        $sampleTasks = [
            ['Sample Task 1 - High Priority', 'This is a high priority task', 'high'],
            ['Sample Task 2 - Medium Priority', 'This is a medium priority task', 'medium'],
            ['Sample Task 3 - Low Priority', 'This is a low priority task', 'low']
        ];
        
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, status, created_at, sla_hours) VALUES (?, ?, ?, ?, ?, 'assigned', NOW(), 2)");
        
        foreach ($sampleTasks as $task) {
            $stmt->execute([$task[0], $task[1], $userId, $userId, $task[2]]);
        }
        
        echo "<p class='success'>✅ Created 3 sample tasks</p>";
    } else {
        echo "<p class='success'>✅ Found $taskCount existing tasks</p>";
    }
    
    echo "<h3>Step 2: Clear and Recreate Daily Tasks</h3>";
    
    // Clear existing daily tasks
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "<p>Cleared existing daily tasks</p>";
    
    // Get ALL active tasks
    $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY priority DESC, created_at DESC");
    $stmt->execute([$userId]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($allTasks) . " active tasks to add to daily planner</p>";
    
    // Create daily tasks for today
    $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())");
    
    foreach ($allTasks as $task) {
        $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
            ? "[From Others] " . $task['title']
            : "[Self] " . $task['title'];
        
        $stmt->execute([
            $userId, 
            $task['id'], 
            $today, 
            $taskTitle, 
            $task['description'] ?? '', 
            120, // 2 hours default
            $task['priority'] ?? 'medium'
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($allTasks) . " daily tasks for today</p>";
    
    echo "<h3>Step 3: Verify Daily Planner</h3>";
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyCount = $stmt->fetchColumn();
    
    echo "<p class='success'>✅ Daily planner now has $dailyCount tasks for today</p>";
    
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p>The Daily Planner should now display all your active tasks.</p>";
    echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;'>Open Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>