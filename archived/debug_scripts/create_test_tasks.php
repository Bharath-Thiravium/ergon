<?php
/**
 * Create test tasks for testing the Planner module fix
 */

session_start();

// Mock session for testing (replace with actual user ID)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Replace with actual user ID for testing
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h1>Create Test Tasks for Planner Module</h1>";
    echo "<p><strong>Creating tasks for User ID:</strong> {$userId}</p>";
    echo "<p><strong>Today's Date:</strong> {$today}</p>";
    echo "<hr>";
    
    // Create test tasks with different scenarios
    $testTasks = [
        [
            'title' => 'Task with Planned Date = Today',
            'description' => 'This task has planned_date set to today',
            'planned_date' => $today,
            'deadline' => null,
            'priority' => 'high'
        ],
        [
            'title' => 'Task with Deadline = Today',
            'description' => 'This task has deadline set to today but no planned_date',
            'planned_date' => null,
            'deadline' => $today . ' 17:00:00',
            'priority' => 'medium'
        ],
        [
            'title' => 'Task Created Today (no dates)',
            'description' => 'This task was created today with no planned_date or deadline',
            'planned_date' => null,
            'deadline' => null,
            'priority' => 'low'
        ],
        [
            'title' => 'In Progress Task',
            'description' => 'This task is currently in progress',
            'planned_date' => date('Y-m-d', strtotime('-1 day')), // Yesterday
            'deadline' => null,
            'priority' => 'high',
            'status' => 'in_progress'
        ],
        [
            'title' => 'Assigned Task (no dates)',
            'description' => 'This task is assigned but has no specific dates',
            'planned_date' => null,
            'deadline' => null,
            'priority' => 'medium',
            'status' => 'assigned'
        ]
    ];
    
    $createdCount = 0;
    
    foreach ($testTasks as $task) {
        $stmt = $db->prepare("
            INSERT INTO tasks (
                title, description, planned_date, deadline, priority, status,
                assigned_to, assigned_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $task['title'],
            $task['description'],
            $task['planned_date'],
            $task['deadline'],
            $task['priority'],
            $task['status'] ?? 'not_started',
            $userId,
            $userId
        ]);
        
        if ($result) {
            $taskId = $db->lastInsertId();
            $createdCount++;
            
            echo "<p style='color: green;'>✅ Created Task ID {$taskId}: {$task['title']}</p>";
            echo "<ul>";
            echo "<li>Planned Date: " . ($task['planned_date'] ?: 'NULL') . "</li>";
            echo "<li>Deadline: " . ($task['deadline'] ?: 'NULL') . "</li>";
            echo "<li>Priority: {$task['priority']}</li>";
            echo "<li>Status: " . ($task['status'] ?? 'not_started') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create: {$task['title']}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p style='color: green; font-size: 16px;'>✅ Created {$createdCount} test tasks successfully!</p>";
    echo "<p>Now you can run <a href='test_planner_fix.php'>test_planner_fix.php</a> to verify the fix works.</p>";
    
    // Show current tasks for this user
    echo "<h3>All Tasks for User {$userId}:</h3>";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, deadline, status, priority, created_at 
        FROM tasks 
        WHERE assigned_to = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allTasks)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Planned Date</th><th>Deadline</th><th>Status</th><th>Priority</th><th>Created</th></tr>";
        foreach ($allTasks as $task) {
            $isToday = ($task['planned_date'] === $today) || 
                      (date('Y-m-d', strtotime($task['deadline'])) === $today) ||
                      (date('Y-m-d', strtotime($task['created_at'])) === $today);
            
            $rowStyle = $isToday ? "background-color: #e8f5e8;" : "";
            
            echo "<tr style='{$rowStyle}'>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>" . ($task['planned_date'] ?: 'NULL') . "</td>";
            echo "<td>" . ($task['deadline'] ? date('Y-m-d H:i', strtotime($task['deadline'])) : 'NULL') . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><em>Tasks highlighted in green should appear in today's planner.</em></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
ul { margin: 5px 0; }
</style>