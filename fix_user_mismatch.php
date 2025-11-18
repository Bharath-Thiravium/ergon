<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Fix User Mismatch Issue</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    echo "<p><strong>Current User ID:</strong> $userId</p>";
    
    // 1. Clear all daily tasks for today
    echo "<h3>1. Clear Daily Tasks</h3>";
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE scheduled_date = ?");
    $stmt->execute([$today]);
    echo "<p class='success'>✅ Cleared all daily tasks for today</p>";
    
    // 2. Create tasks for current user
    echo "<h3>2. Create Tasks for User $userId</h3>";
    
    // Check if user has any tasks
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
    $stmt->execute([$userId]);
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount == 0) {
        // Create sample tasks for current user
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, status, sla_hours, created_at) VALUES (?, ?, ?, ?, 'assigned', 2, NOW())");
        
        $sampleTasks = [
            'Complete Project Documentation',
            'Review Code Changes', 
            'Update Database Schema'
        ];
        
        foreach ($sampleTasks as $title) {
            $stmt->execute([$title, "Sample task: $title", $userId, $userId]);
        }
        
        echo "<p class='success'>✅ Created 3 sample tasks for user $userId</p>";
    } else {
        echo "<p class='success'>✅ User $userId has $taskCount existing tasks</p>";
    }
    
    // 3. Create daily tasks for current user
    $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' LIMIT 5");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tasks as $task) {
        $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'not_started', NOW())");
        $stmt->execute([
            $userId,
            $task['id'],
            $today,
            $task['title'],
            $task['description'] ?? '',
            $task['priority'] ?? 'medium'
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($tasks) . " daily tasks for user $userId</p>";
    
    // 4. Verify
    echo "<h3>3. Verification</h3>";
    $stmt = $db->prepare("SELECT id, title, user_id FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Daily Task ID</th><th>Title</th><th>User ID</th></tr>";
    foreach ($dailyTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['user_id']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p class='success'>✅ All tasks now belong to user $userId</p>";
    echo "<p><a href='/ergon/workflow/daily-planner'>Test Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>