<?php
/**
 * Debug Planner Data Flow - Step by Step Analysis
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Mock for testing
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h1>Planner Data Flow Debug</h1>";
    echo "<p><strong>User ID:</strong> {$userId} | <strong>Date:</strong> {$today}</p>";
    echo "<style>table{border-collapse:collapse;width:100%;margin:10px 0;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.success{color:green;}.error{color:red;}.warning{color:orange;}</style>";
    
    echo "<hr>";
    
    // STEP 1: Check raw tasks data
    echo "<h2>STEP 1: Raw Tasks Data</h2>";
    
    $stmt = $db->prepare("
        SELECT id, title, planned_date, deadline, status, assigned_to, assigned_by, created_at, sla_hours
        FROM tasks 
        WHERE assigned_to = ?
        AND status NOT IN ('completed', 'cancelled', 'deleted')
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $rawTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total active tasks for user: " . count($rawTasks) . "</p>";
    
    if (empty($rawTasks)) {
        echo "<p class='error'>❌ NO TASKS FOUND! This is the root cause.</p>";
        echo "<p>Creating test task...</p>";
        
        // Create a test task
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, planned_date, assigned_to, assigned_by, status, sla_hours, created_at)
            VALUES (?, ?, ?, ?, ?, 'not_started', 0.25, NOW())
        ");
        $result = $stmt->execute([
            'Test Task for Today',
            'This is a test task created for debugging',
            $today,
            $userId,
            $userId
        ]);
        
        if ($result) {
            echo "<p class='success'>✅ Test task created with ID: " . $db->lastInsertId() . "</p>";
            
            // Re-fetch tasks
            $stmt = $db->prepare("
                SELECT id, title, planned_date, deadline, status, assigned_to, assigned_by, created_at, sla_hours
                FROM tasks 
                WHERE assigned_to = ?
                AND status NOT IN ('completed', 'cancelled', 'deleted')
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $rawTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    if (!empty($rawTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Planned Date</th><th>Deadline</th><th>Status</th><th>SLA Hours</th><th>Should Show Today?</th></tr>";
        
        foreach ($rawTasks as $task) {
            $shouldShowToday = false;
            $reason = '';
            
            if ($task['planned_date'] === $today) {
                $shouldShowToday = true;
                $reason = 'Planned Date = Today';
            } elseif ($task['deadline'] && date('Y-m-d', strtotime($task['deadline'])) === $today) {
                $shouldShowToday = true;
                $reason = 'Deadline = Today';
            } elseif (date('Y-m-d', strtotime($task['created_at'])) === $today && !$task['planned_date'] && !$task['deadline']) {
                $shouldShowToday = true;
                $reason = 'Created Today (no dates)';
            } elseif ($task['status'] === 'in_progress') {
                $shouldShowToday = true;
                $reason = 'In Progress';
            }
            
            $rowClass = $shouldShowToday ? 'success' : '';
            
            echo "<tr class='{$rowClass}'>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>" . ($task['planned_date'] ?? 'NULL') . "</td>";
            echo "<td>" . ($task['deadline'] ?? 'NULL') . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['sla_hours'] ?? '0.25') . "</td>";
            echo "<td>" . ($shouldShowToday ? "✅ YES - {$reason}" : "❌ NO") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // STEP 2: Test fetchAssignedTasksForDate
    echo "<h2>STEP 2: Test fetchAssignedTasksForDate</h2>";
    
    $addedCount = $planner->fetchAssignedTasksForDate($userId, $today);
    echo "<p>Tasks added to daily_tasks: {$addedCount}</p>";
    
    // Check daily_tasks after fetch
    $stmt = $db->prepare("
        SELECT id, user_id, task_id, original_task_id, title, scheduled_date, status, source_field, created_at
        FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Daily tasks count: " . count($dailyTasks) . "</p>";
    
    if (!empty($dailyTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Task ID</th><th>Original Task ID</th><th>Title</th><th>Status</th><th>Source</th><th>Created</th></tr>";
        
        foreach ($dailyTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . ($task['task_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($task['original_task_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['source_field'] ?? 'NULL') . "</td>";
            echo "<td>" . date('H:i:s', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No daily tasks created! Issue in fetchAssignedTasksForDate method.</p>";
    }
    
    echo "<hr>";
    
    // STEP 3: Test getTasksForDate
    echo "<h2>STEP 3: Test getTasksForDate (Final Result)</h2>";
    
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    echo "<p>Final tasks returned: " . count($plannedTasks) . "</p>";
    
    if (empty($plannedTasks)) {
        echo "<p class='error'>❌ FINAL ISSUE: getTasksForDate returned no tasks!</p>";
        
        // Debug the getTasksForDate query
        echo "<h3>Debug getTasksForDate Query</h3>";
        
        // Let's manually run the query from getTasksForDate
        $stmt = $db->prepare("
            SELECT 
                dt.id, dt.title, dt.description, dt.priority, dt.status,
                dt.completed_percentage, dt.start_time, dt.active_seconds,
                dt.planned_duration, dt.task_id, dt.original_task_id, dt.pause_duration,
                dt.completion_time, dt.postponed_from_date, dt.postponed_to_date,
                dt.created_at, dt.scheduled_date, dt.source_field, dt.rollover_source_date,
                COALESCE(t.sla_hours, 0.25) as sla_hours,
                CASE 
                    WHEN dt.rollover_source_date IS NOT NULL THEN CONCAT('🔄 Rolled over from: ', dt.rollover_source_date)
                    WHEN dt.source_field IS NOT NULL THEN CONCAT('📌 Source: ', dt.source_field, ' on ', dt.scheduled_date)
                    WHEN t.assigned_by != t.assigned_to THEN '👥 From Others'
                    ELSE '👤 Self-Assigned'
                END as task_indicator,
                'current_day' as view_type
            FROM daily_tasks dt
            LEFT JOIN tasks t ON dt.original_task_id = t.id
            WHERE dt.user_id = ? AND dt.scheduled_date = ?
            ORDER BY 
                CASE WHEN dt.rollover_source_date IS NOT NULL THEN 0 ELSE 1 END,
                CASE dt.status 
                    WHEN 'in_progress' THEN 1 
                    WHEN 'on_break' THEN 2 
                    WHEN 'not_started' THEN 3
                    WHEN 'postponed' THEN 5
                    ELSE 4 
                END, 
                CASE dt.priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                    ELSE 4 
                END
        ");
        $stmt->execute([$userId, $today]);
        $debugTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Direct query result: " . count($debugTasks) . " tasks</p>";
        
        if (!empty($debugTasks)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>SLA Hours</th><th>Task Indicator</th></tr>";
            
            foreach ($debugTasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>" . htmlspecialchars($task['title']) . "</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['sla_hours']}</td>";
                echo "<td>" . htmlspecialchars($task['task_indicator']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p class='success'>✅ SUCCESS: Found " . count($plannedTasks) . " tasks!</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>SLA Hours</th><th>Source</th></tr>";
        
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>" . ($task['sla_hours'] ?? '0.25') . "</td>";
            echo "<td>" . ($task['source_field'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // STEP 4: Test API endpoint
    echo "<h2>STEP 4: Test API Endpoint</h2>";
    
    $apiUrl = "/ergon/api/daily_planner_workflow.php?action=get_tasks&date={$today}&user_id={$userId}";
    echo "<p>API URL: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
    
    // Test the API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost{$apiUrl}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: {$httpCode}</p>";
    echo "<p>API Response:</p>";
    echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
    
    echo "<hr>";
    
    // STEP 5: Summary and Recommendations
    echo "<h2>STEP 5: Summary and Recommendations</h2>";
    
    $issues = [];
    $fixes = [];
    
    if (empty($rawTasks)) {
        $issues[] = "No tasks found in tasks table for user {$userId}";
        $fixes[] = "Create tasks with planned_date = today or deadline = today";
    }
    
    if ($addedCount == 0 && !empty($rawTasks)) {
        $issues[] = "fetchAssignedTasksForDate is not adding tasks to daily_tasks table";
        $fixes[] = "Check fetchAssignedTasksForDate method logic and database permissions";
    }
    
    if (empty($dailyTasks) && $addedCount > 0) {
        $issues[] = "Tasks were supposedly added but not found in daily_tasks table";
        $fixes[] = "Check database transaction handling and table structure";
    }
    
    if (empty($plannedTasks) && !empty($dailyTasks)) {
        $issues[] = "getTasksForDate is not returning tasks from daily_tasks table";
        $fixes[] = "Check getTasksForDate method query and JOIN conditions";
    }
    
    if ($httpCode != 200) {
        $issues[] = "API endpoint is not responding correctly (HTTP {$httpCode})";
        $fixes[] = "Check API routing and authentication";
    }
    
    if (empty($issues)) {
        echo "<p class='success'>✅ All checks passed! The planner should be working correctly.</p>";
    } else {
        echo "<h3>Issues Found:</h3>";
        foreach ($issues as $issue) {
            echo "<p class='error'>❌ {$issue}</p>";
        }
        
        echo "<h3>Recommended Fixes:</h3>";
        foreach ($fixes as $fix) {
            echo "<p class='warning'>🔧 {$fix}</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>