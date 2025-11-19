<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Simulate user session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to your user ID
    $_SESSION['role'] = 'owner';
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h2>Daily Planner Fix Test</h2>";
    echo "<p>User ID: <strong>$userId</strong></p>";
    echo "<p>Today's Date: <strong>$today</strong></p>";
    echo "<hr>";
    
    // Add sample tasks if requested
    if (isset($_GET['add_tasks'])) {
        echo "<h3>Adding Sample Tasks...</h3>";
        
        // Task assigned by another user (simulating task from others)
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, status, created_at, assigned_at, deadline)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ");
        
        $tasks = [
            [
                'title' => 'Review Client Proposal - Urgent',
                'description' => 'Review and provide feedback on the new client proposal document',
                'assigned_by' => 2, // Different user
                'assigned_to' => $userId,
                'priority' => 'high',
                'status' => 'assigned',
                'deadline' => $today
            ],
            [
                'title' => 'Complete Daily Report',
                'description' => 'Prepare and submit the daily activity report',
                'assigned_by' => $userId, // Self-assigned
                'assigned_to' => $userId,
                'priority' => 'medium',
                'status' => 'assigned',
                'deadline' => $today
            ],
            [
                'title' => 'Team Meeting Preparation',
                'description' => 'Prepare agenda and materials for tomorrow\'s team meeting',
                'assigned_by' => 3, // Different user
                'assigned_to' => $userId,
                'priority' => 'medium',
                'status' => 'assigned',
                'deadline' => $today
            ],
            [
                'title' => 'Update Project Documentation',
                'description' => 'Update the project documentation with latest changes',
                'assigned_by' => $userId, // Self-assigned
                'assigned_to' => $userId,
                'priority' => 'low',
                'status' => 'assigned',
                'deadline' => date('Y-m-d', strtotime('+1 day'))
            ]
        ];
        
        foreach ($tasks as $task) {
            $stmt->execute([
                $task['title'],
                $task['description'],
                $task['assigned_by'],
                $task['assigned_to'],
                $task['priority'],
                $task['status'],
                $task['deadline']
            ]);
        }
        
        echo "<p style='color: green;'>✅ Added " . count($tasks) . " sample tasks</p>";
        echo "<p><a href='?'>Refresh to see results</a></p>";
        echo "<hr>";
    }
    
    // Test the fixed query
    echo "<h3>Testing Fixed Query (Tasks for Today):</h3>";
    $stmt = $db->prepare("
        SELECT *, 
               CASE 
                   WHEN assigned_by != assigned_to THEN 'From Others' 
                   ELSE 'Self-Assigned' 
               END as task_source
        FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(created_at) = ? OR
            DATE(deadline) = ? OR
            DATE(planned_date) = ? OR
            status = 'in_progress' OR
            (assigned_by != assigned_to AND DATE(COALESCE(assigned_at, created_at)) = ?)
        )
        AND status != 'completed' 
        ORDER BY 
            CASE 
                WHEN assigned_by != assigned_to THEN 1
                ELSE 2
            END,
            CASE priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END,
            created_at DESC 
        LIMIT 15
    ");
    $stmt->execute([$userId, $today, $today, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayTasks)) {
        echo "<p style='color: red;'>❌ No tasks found for today</p>";
        echo "<p><a href='?add_tasks=1' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Add Sample Tasks</a></p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($todayTasks) . " tasks for today</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Title</th><th>Source</th><th>Priority</th><th>Status</th><th>Deadline</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($todayTasks as $task) {
            $rowColor = ($task['task_source'] === 'From Others') ? 'background: #fff3cd;' : 'background: #d1ecf1;';
            echo "<tr style='$rowColor'>";
            echo "<td><strong>{$task['title']}</strong><br><small>{$task['description']}</small></td>";
            echo "<td><strong>{$task['task_source']}</strong></td>";
            echo "<td><span style='padding: 2px 6px; border-radius: 3px; background: " . 
                 ($task['priority'] === 'high' ? '#dc3545' : ($task['priority'] === 'medium' ? '#ffc107' : '#28a745')) . 
                 "; color: white; font-size: 11px;'>{$task['priority']}</span></td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['deadline'] ? date('M j', strtotime($task['deadline'])) : 'No deadline') . "</td>";
            echo "<td>" . date('M j, H:i', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Summary
        $fromOthers = count(array_filter($todayTasks, fn($t) => $t['task_source'] === 'From Others'));
        $selfAssigned = count(array_filter($todayTasks, fn($t) => $t['task_source'] === 'Self-Assigned'));
        
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Summary for Today:</h4>";
        echo "<p>📋 <strong>Total Tasks:</strong> " . count($todayTasks) . "</p>";
        echo "<p>👥 <strong>Assigned by Others:</strong> $fromOthers</p>";
        echo "<p>👤 <strong>Self-Assigned:</strong> $selfAssigned</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    
    // Test daily planner controller simulation
    echo "<h3>Testing Daily Planner Controller Logic:</h3>";
    
    // Clear existing daily tasks for today
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    
    // Simulate controller logic
    if (!empty($todayTasks)) {
        echo "<p>Creating daily tasks from regular tasks...</p>";
        
        foreach ($todayTasks as $task) {
            $taskSource = ($task['assigned_by'] != $task['assigned_to']) ? 'assigned_by_others' : 'self_assigned';
            $taskTitle = $task['title'];
            
            // Add source indicator to title for clarity
            if ($taskSource === 'assigned_by_others') {
                $taskTitle = "[From Others] " . $taskTitle;
            } else {
                $taskTitle = "[Self] " . $taskTitle;
            }
            
            $stmt = $db->prepare("
                INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, 60, ?, 'not_started', NOW())
            ");
            $stmt->execute([
                $userId, 
                $task['id'], 
                $today, 
                $taskTitle, 
                $task['description'], 
                $task['priority'] ?? 'medium'
            ]);
        }
        
        echo "<p style='color: green;'>✅ Created " . count($todayTasks) . " daily tasks</p>";
    }
    
    echo "<hr>";
    
    // Quick actions
    echo "<h3>Quick Actions:</h3>";
    echo "<p>";
    echo "<a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🗓 View Daily Planner</a>";
    echo "<a href='?add_tasks=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>➕ Add Sample Tasks</a>";
    echo "<a href='/ergon/tasks/create' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>📝 Create New Task</a>";
    echo "</p>";
    
    // Clear tasks option
    if (isset($_GET['clear_tasks'])) {
        $stmt = $db->prepare("DELETE FROM tasks WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo "<p style='color: green; margin-top: 15px;'>✅ Cleared all tasks. <a href='?'>Refresh</a></p>";
    } else {
        echo "<p style='margin-top: 15px;'>";
        echo "<a href='?clear_tasks=1' style='background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;'>🗑 Clear All Tasks (for testing)</a>";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
    background: #f8f9fa;
}
table { 
    margin: 10px 0; 
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th, td { 
    padding: 12px; 
    text-align: left; 
    border-bottom: 1px solid #dee2e6;
}
th { 
    font-weight: 600; 
    background: #e9ecef;
}
h2, h3 {
    color: #495057;
}
hr {
    border: none;
    height: 1px;
    background: #dee2e6;
    margin: 20px 0;
}
</style>