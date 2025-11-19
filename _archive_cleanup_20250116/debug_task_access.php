<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$db = Database::connect();
$userId = $_SESSION['user_id'];

echo "<h2>Task Access Debug</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

// Check what tasks exist and their IDs
$stmt = $db->prepare("SELECT id, user_id, title, status FROM daily_tasks WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Daily Tasks for User $userId:</h3>";
if (empty($tasks)) {
    echo "<p class='error'>No daily tasks found</p>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Title</th><th>Status</th></tr>";
    foreach ($tasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['user_id']}</td><td>{$task['title']}</td><td>{$task['status']}</td></tr>";
    }
    echo "</table>";
}

// Check regular tasks table
$stmt = $db->prepare("SELECT id, assigned_to, title, status FROM tasks WHERE assigned_to = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$userId]);
$regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Regular Tasks for User $userId:</h3>";
if (empty($regularTasks)) {
    echo "<p class='error'>No regular tasks found</p>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>Assigned To</th><th>Title</th><th>Status</th></tr>";
    foreach ($regularTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['assigned_to']}</td><td>{$task['title']}</td><td>{$task['status']}</td></tr>";
    }
    echo "</table>";
}
?>