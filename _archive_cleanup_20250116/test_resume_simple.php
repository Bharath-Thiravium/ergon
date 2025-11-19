<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$db = Database::connect();
$userId = $_SESSION['user_id'];

echo "<h2>Simple Resume Test</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

// Create a test task in paused state
$stmt = $db->prepare("INSERT INTO daily_tasks (user_id, title, scheduled_date, status, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, 'Test Resume Task', date('Y-m-d'), 'on_break']);
$testTaskId = $db->lastInsertId();

echo "<p>Created test task with ID: $testTaskId</p>";

// Test the resume functionality directly
$stmt = $db->prepare("SELECT id, status FROM daily_tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$testTaskId, $userId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task) {
    echo "<p class='success'>✅ Task found: ID {$task['id']}, Status: {$task['status']}</p>";
    
    if (in_array($task['status'], ['paused', 'on_break'])) {
        echo "<p class='success'>✅ Task can be resumed</p>";
        
        // Try to resume
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', resume_time = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Task resumed successfully</p>";
        } else {
            echo "<p class='error'>❌ Failed to resume task</p>";
        }
    } else {
        echo "<p class='error'>❌ Task cannot be resumed. Status: {$task['status']}</p>";
    }
} else {
    echo "<p class='error'>❌ Task not found</p>";
}

// Clean up
$stmt = $db->prepare("DELETE FROM daily_tasks WHERE id = ?");
$stmt->execute([$testTaskId]);
echo "<p>Test task cleaned up</p>";

echo "<p><a href='/ergon/workflow/daily-planner'>Test in Daily Planner</a></p>";
?>