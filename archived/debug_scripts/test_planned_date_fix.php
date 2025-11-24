<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    $userId = 1;
    $today = date('Y-m-d');
    $futureDate = date('Y-m-d', strtotime('+3 days'));
    
    echo "<h1>Test Planned Date Fix</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}</style>";
    
    echo "<p><strong>Today:</strong> {$today}</p>";
    echo "<p><strong>Future Date:</strong> {$futureDate}</p>";
    
    // Create a task with future planned date
    echo "<h2>Step 1: Create Task with Future Planned Date</h2>";
    $stmt = $db->prepare("INSERT INTO tasks (title, description, planned_date, assigned_to, assigned_by, status, created_at) VALUES (?, ?, ?, ?, ?, 'assigned', NOW())");
    $result = $stmt->execute([
        'Future Task - Created Today',
        'This task is created today but planned for future date',
        $futureDate,
        $userId,
        $userId
    ]);
    
    if ($result) {
        $taskId = $db->lastInsertId();
        echo "<p class='success'>✅ Created task ID {$taskId} with planned_date = {$futureDate}</p>";
    } else {
        echo "<p class='error'>❌ Failed to create task</p>";
        exit;
    }
    
    // Test today's planner - should NOT show the future task
    echo "<h2>Step 2: Test Today's Planner ({$today})</h2>";
    $todayTasks = $planner->getTasksForDate($userId, $today);
    echo "<p>Tasks found for today: " . count($todayTasks) . "</p>";
    
    $futureTaskInToday = false;
    foreach ($todayTasks as $task) {
        if ($task['task_id'] == $taskId) {
            $futureTaskInToday = true;
            break;
        }
    }
    
    if ($futureTaskInToday) {
        echo "<p class='error'>❌ FAILED: Future task appears in today's planner!</p>";
    } else {
        echo "<p class='success'>✅ SUCCESS: Future task does NOT appear in today's planner</p>";
    }
    
    // Test future date planner - should show the task
    echo "<h2>Step 3: Test Future Date Planner ({$futureDate})</h2>";
    $futureTasks = $planner->getTasksForDate($userId, $futureDate);
    echo "<p>Tasks found for future date: " . count($futureTasks) . "</p>";
    
    $futureTaskInFuture = false;
    foreach ($futureTasks as $task) {
        if ($task['task_id'] == $taskId) {
            $futureTaskInFuture = true;
            echo "<p class='info'>Task details: ID={$task['id']}, Title='{$task['title']}', Source={$task['source_field']}</p>";
            break;
        }
    }
    
    if ($futureTaskInFuture) {
        echo "<p class='success'>✅ SUCCESS: Future task appears correctly in future date planner</p>";
    } else {
        echo "<p class='error'>❌ FAILED: Future task does NOT appear in future date planner!</p>";
    }
    
    // Summary
    echo "<h2>Test Results</h2>";
    if (!$futureTaskInToday && $futureTaskInFuture) {
        echo "<div style='padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;'>";
        echo "<h3 style='color:#155724;margin:0 0 10px 0;'>🎉 PLANNED DATE FIX SUCCESSFUL!</h3>";
        echo "<p style='margin:0;'>Tasks now appear ONLY on their planned date, not on creation date.</p>";
        echo "</div>";
    } else {
        echo "<div style='padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;'>";
        echo "<h3 style='color:#721c24;margin:0 0 10px 0;'>❌ Fix Still Needs Work</h3>";
        echo "<p style='margin:0;'>Tasks are still appearing based on creation date or not appearing on planned date.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>