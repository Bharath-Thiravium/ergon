<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Force Refresh Daily Planner</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;}</style>";

try {
    $db = Database::connect();
    
    // 1. Delete ALL daily tasks (force clean slate)
    $db->exec("DELETE FROM daily_tasks");
    echo "<p class='success'>✅ Deleted ALL daily tasks</p>";
    
    // 2. Create fresh task for current user
    $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, status, sla_hours, created_at) VALUES (?, ?, ?, ?, 'assigned', 2, NOW())");
    $stmt->execute(['Test SLA Task - ' . date('H:i:s'), 'Test task for SLA functionality', $userId, $userId]);
    $newTaskId = $db->lastInsertId();
    
    // 3. Create daily task for current user
    $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, priority, status, created_at) VALUES (?, ?, ?, ?, ?, 'medium', 'not_started', NOW())");
    $stmt->execute([$userId, $newTaskId, $today, 'Test SLA Task - ' . date('H:i:s'), 'Test task for SLA functionality']);
    $dailyTaskId = $db->lastInsertId();
    
    echo "<p class='success'>✅ Created fresh task for user $userId</p>";
    echo "<p><strong>Daily Task ID:</strong> $dailyTaskId</p>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // 4. Verify
    $stmt = $db->prepare("SELECT id, user_id, title FROM daily_tasks WHERE id = ?");
    $stmt->execute([$dailyTaskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Verification: Task ID {$task['id']} belongs to user {$task['user_id']}</p>";
    
    echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;'>Open Daily Planner Now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>