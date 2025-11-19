<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Debug Task Flow - Root Cause Analysis</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;}</style>";

try {
    $db = Database::connect();
    
    echo "<p><strong>User ID:</strong> $userId | <strong>Today:</strong> $today</p>";
    
    // 1. Check ALL tasks for this user
    echo "<h3>1. ALL Tasks for User $userId</h3>";
    $stmt = $db->prepare("SELECT id, title, assigned_to, assigned_by, status, created_at, deadline, planned_date FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allTasks)) {
        echo "<p class='error'>❌ NO TASKS FOUND for user $userId</p>";
        echo "<p>Creating test task...</p>";
        
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, status, created_at, sla_hours) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute(['Test Task - ' . date('H:i:s'), 'Test task for debugging', $userId, $userId, 'assigned', 2]);
        
        // Re-fetch
        $stmt = $db->prepare("SELECT id, title, assigned_to, assigned_by, status, created_at, deadline, planned_date FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<p class='success'>Found " . count($allTasks) . " total tasks</p>";
    echo "<table><tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Deadline</th><th>Planned Date</th></tr>";
    foreach ($allTasks as $task) {
        echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['created_at']}</td><td>{$task['deadline']}</td><td>{$task['planned_date']}</td></tr>";
    }
    echo "</table>";
    
    // 2. Check what the current query returns
    echo "<h3>2. Current Daily Planner Query Result</h3>";
    $stmt = $db->prepare("
        SELECT *, COALESCE(sla_hours, 1) as sla_hours FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(created_at) = ? OR
            DATE(deadline) = ? OR
            DATE(planned_date) = ? OR
            DATE(updated_at) = ? OR
            status = 'in_progress'
        )
        AND status != 'completed' 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $today, $today, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query returned " . count($todayTasks) . " tasks</p>";
    if (!empty($todayTasks)) {
        echo "<table><tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Match Reason</th></tr>";
        foreach ($todayTasks as $task) {
            $reason = [];
            if (date('Y-m-d', strtotime($task['created_at'])) === $today) $reason[] = 'Created Today';
            if ($task['deadline'] && date('Y-m-d', strtotime($task['deadline'])) === $today) $reason[] = 'Deadline Today';
            if ($task['planned_date'] && date('Y-m-d', strtotime($task['planned_date'])) === $today) $reason[] = 'Planned Today';
            if ($task['status'] === 'in_progress') $reason[] = 'In Progress';
            
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['created_at']}</td><td>" . implode(', ', $reason) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ NO TASKS match today's criteria</p>";
    }
    
    // 3. Check daily_tasks table
    echo "<h3>3. Daily Tasks Table</h3>";
    $stmt = $db->prepare("SELECT id, title, status, task_id, scheduled_date FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Daily tasks table has " . count($dailyTasks) . " tasks for today</p>";
    if (!empty($dailyTasks)) {
        echo "<table><tr><th>Daily ID</th><th>Title</th><th>Status</th><th>Linked Task ID</th></tr>";
        foreach ($dailyTasks as $task) {
            echo "<tr><td>{$task['id']}</td><td>{$task['title']}</td><td>{$task['status']}</td><td>{$task['task_id']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 4. FORCE CREATE daily tasks from ALL tasks (remove restrictions)
    echo "<h3>4. Force Create Daily Tasks (No Restrictions)</h3>";
    
    // Clear existing
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    
    // Get ALL non-completed tasks for this user
    $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $allActiveTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Creating daily tasks from " . count($allActiveTasks) . " active tasks</p>";
    
    foreach ($allActiveTasks as $task) {
        $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
            ? "[From Others] " . $task['title']
            : "[Self] " . $task['title'];
        
        $stmt = $db->prepare("
            INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
        ");
        $stmt->execute([
            $userId, $task['id'], $today, $taskTitle, 
            $task['description'] ?? '', 120, $task['priority'] ?? 'medium'
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($allActiveTasks) . " daily tasks</p>";
    
    echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Daily Planner Now</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>