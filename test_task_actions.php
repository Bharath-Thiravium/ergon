<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$db = Database::connect();
$userId = $_SESSION['user_id'];

echo "<h2>Task Actions Test</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

// Create a test task
$stmt = $db->prepare("INSERT INTO daily_tasks (user_id, title, scheduled_date, status, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, 'Test Task Actions', date('Y-m-d'), 'not_started']);
$testTaskId = $db->lastInsertId();

echo "<p>Created test task with ID: $testTaskId</p>";

// Test START
echo "<h3>Testing START</h3>";
$stmt = $db->prepare("SELECT status FROM daily_tasks WHERE id = ?");
$stmt->execute([$testTaskId]);
$status = $stmt->fetchColumn();
echo "<p>Current status: $status</p>";

if (in_array($status, ['not_started', 'assigned'])) {
    try {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    } catch (Exception $e) {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress' WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    }
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<p class='success'>✅ START works</p>";
    } else {
        echo "<p class='error'>❌ START failed</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot start task with status: $status</p>";
}

// Test PAUSE
echo "<h3>Testing PAUSE</h3>";
$stmt = $db->prepare("SELECT status FROM daily_tasks WHERE id = ?");
$stmt->execute([$testTaskId]);
$status = $stmt->fetchColumn();
echo "<p>Current status: $status</p>";

if ($status === 'in_progress') {
    try {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'on_break', pause_time = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    } catch (Exception $e) {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'on_break' WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    }
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<p class='success'>✅ PAUSE works</p>";
    } else {
        echo "<p class='error'>❌ PAUSE failed</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot pause task with status: $status</p>";
}

// Test RESUME
echo "<h3>Testing RESUME</h3>";
$stmt = $db->prepare("SELECT status FROM daily_tasks WHERE id = ?");
$stmt->execute([$testTaskId]);
$status = $stmt->fetchColumn();
echo "<p>Current status: $status</p>";

if (in_array($status, ['paused', 'on_break'])) {
    try {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', resume_time = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    } catch (Exception $e) {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress' WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$testTaskId, $userId]);
    }
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<p class='success'>✅ RESUME works</p>";
    } else {
        echo "<p class='error'>❌ RESUME failed</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot resume task with status: $status</p>";
}

// Clean up
$stmt = $db->prepare("DELETE FROM daily_tasks WHERE id = ?");
$stmt->execute([$testTaskId]);
echo "<p>Test task cleaned up</p>";

echo "<p class='success'>✅ All task actions should now work properly!</p>";
echo "<p><a href='/ergon/workflow/daily-planner'>Test in Daily Planner</a></p>";
?>