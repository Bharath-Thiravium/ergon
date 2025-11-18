<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$db = Database::connect();
$userId = $_SESSION['user_id'];

echo "<h2>Debug Task IDs</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

echo "<p><strong>User ID:</strong> $userId</p>";
echo "<p><strong>Today:</strong> " . date('Y-m-d') . "</p>";

// Check what tasks exist in daily_tasks
echo "<h3>Tasks in daily_tasks table:</h3>";
$stmt = $db->prepare("SELECT id, user_id, title, status, scheduled_date FROM daily_tasks WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($dailyTasks)) {
    echo "<p class='error'>No tasks found in daily_tasks table for user $userId</p>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Title</th><th>Status</th><th>Date</th></tr>";
    foreach ($dailyTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['user_id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['scheduled_date']}</td></tr>";
    }
    echo "</table>";
}

// Check what the controller would create
echo "<h3>What controller would create:</h3>";
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT id, title, assigned_to, assigned_by, status FROM tasks 
    WHERE assigned_to = ? 
    AND (
        DATE(created_at) = ? OR
        DATE(deadline) = ? OR
        DATE(planned_date) = ? OR
        status = 'in_progress' OR
        (assigned_by != assigned_to AND DATE(assigned_at) = ?)
    )
    AND status != 'completed' 
    ORDER BY 
        CASE 
            WHEN assigned_by != assigned_to THEN 1
            ELSE 2
        END,
        created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId, $today, $today, $today, $today]);
$regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($regularTasks)) {
    echo "<p class='error'>No regular tasks found for today</p>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Status</th></tr>";
    foreach ($regularTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['assigned_to']}</td><td>{$task['status']}</td></tr>";
    }
    echo "</table>";
}

// Test API call simulation
if (!empty($dailyTasks)) {
    $testTaskId = $dailyTasks[0]['id'];
    echo "<h3>Test API Call for Task ID: $testTaskId</h3>";
    
    // Simulate the API validation
    $stmt = $db->prepare("SELECT id, status FROM daily_tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$testTaskId, $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo "<p class='success'>✅ Task found: ID {$task['id']}, Status: {$task['status']}</p>";
    } else {
        echo "<p class='error'>❌ Task not found with ID $testTaskId and user $userId</p>";
    }
}

echo "<p><a href='/ergon/workflow/daily-planner'>Back to Daily Planner</a></p>";
?>