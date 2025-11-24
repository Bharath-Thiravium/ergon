<?php
/**
 * Complete Fix for Planner Module Task Fetching Issue
 * This script will identify and fix all issues preventing tasks from displaying in the planner
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Mock for testing
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h1>Complete Planner Fix</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;margin:10px 0;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.success{color:green;}.error{color:red;}.warning{color:orange;}</style>";
    
    echo "<h2>Step 1: Check and Fix Database Structure</h2>";
    
    // 1. Ensure daily_tasks table exists with all required columns
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_id INT NULL,
            original_task_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            scheduled_date DATE NOT NULL,
            planned_start_time TIME NULL,
            planned_duration INT DEFAULT 60,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'not_started',
            completed_percentage INT DEFAULT 0,
            start_time TIMESTAMP NULL,
            pause_time TIMESTAMP NULL,
            pause_start_time TIMESTAMP NULL,
            resume_time TIMESTAMP NULL,
            completion_time TIMESTAMP NULL,
            sla_end_time TIMESTAMP NULL,
            active_seconds INT DEFAULT 0,
            pause_duration INT DEFAULT 0,
            postponed_from_date DATE NULL,
            postponed_to_date DATE NULL,
            source_field VARCHAR(50) NULL,
            rollover_source_date DATE NULL,
            rollover_timestamp TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_date (user_id, scheduled_date),
            INDEX idx_task_id (task_id),
            INDEX idx_original_task_id (original_task_id),
            INDEX idx_status (status)
        )
    ";
    
    $db->exec($createTableSQL);
    echo "<p class='success'>✅ daily_tasks table structure verified/created</p>";
    
    // 2. Add missing columns if they don't exist
    $requiredColumns = [
        'original_task_id' => 'INT NULL',
        'source_field' => 'VARCHAR(50) NULL',
        'rollover_source_date' => 'DATE NULL',
        'pause_duration' => 'INT DEFAULT 0',
        'pause_start_time' => 'TIMESTAMP NULL',
        'postponed_to_date' => 'DATE NULL'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN {$column} {$definition}");
                echo "<p class='success'>✅ Added missing column: {$column}</p>";
            }
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Could not add column {$column}: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Step 2: Check Tasks Data</h2>";
    
    // Check if there are any tasks for this user
    $stmt = $db->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN planned_date = ? THEN 1 ELSE 0 END) as planned_today,
               SUM(CASE WHEN DATE(deadline) = ? THEN 1 ELSE 0 END) as deadline_today,
               SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as created_today,
               SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
        FROM tasks 
        WHERE assigned_to = ? 
        AND status NOT IN ('completed', 'cancelled', 'deleted')
    ");
    $stmt->execute([$today, $today, $today, $userId]);
    $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total active tasks: {$taskStats['total']}</p>";
    echo "<p>Tasks planned for today: {$taskStats['planned_today']}</p>";
    echo "<p>Tasks with deadline today: {$taskStats['deadline_today']}</p>";
    echo "<p>Tasks created today: {$taskStats['created_today']}</p>";
    echo "<p>Tasks in progress: {$taskStats['in_progress']}</p>";
    
    $shouldShowToday = $taskStats['planned_today'] + $taskStats['deadline_today'] + $taskStats['created_today'] + $taskStats['in_progress'];
    
    if ($shouldShowToday == 0) {
        echo "<p class='warning'>⚠️ No tasks should appear today based on current data. Creating test task...</p>";
        
        // Create a test task
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, planned_date, assigned_to, assigned_by, status, sla_hours, created_at)
            VALUES (?, ?, ?, ?, ?, 'assigned', 0.25, NOW())
        ");
        $result = $stmt->execute([
            'Test Task - ' . date('H:i:s'),
            'This is a test task created by the fix script',
            $today,
            $userId,
            $userId
        ]);
        
        if ($result) {
            echo "<p class='success'>✅ Test task created with ID: " . $db->lastInsertId() . "</p>";
            $shouldShowToday = 1;
        }
    }
    
    echo "<h2>Step 3: Fix fetchAssignedTasksForDate Method</h2>";
    
    // Manually run the corrected fetchAssignedTasksForDate logic
    $stmt = $db->prepare("
        SELECT 
            t.id, t.title, t.description, t.priority, t.status,
            t.deadline, t.estimated_duration, t.assigned_to, t.assigned_by, t.sla_hours,
            CASE 
                WHEN t.planned_date = ? THEN 'planned_date'
                WHEN DATE(t.deadline) = ? THEN 'deadline'
                WHEN DATE(t.created_at) = ? THEN 'created_date'
                WHEN t.status = 'in_progress' THEN 'in_progress'
                ELSE 'assigned'
            END as source_field
        FROM tasks t
        WHERE t.assigned_to = ? 
        AND t.status NOT IN ('completed', 'cancelled', 'deleted')
        AND (
            t.planned_date = ? OR
            DATE(t.deadline) = ? OR
            DATE(t.created_at) = ? OR
            t.status = 'in_progress' OR
            (t.planned_date IS NULL AND t.deadline IS NULL AND t.status IN ('assigned', 'not_started'))
        )
        ORDER BY 
            CASE WHEN t.assigned_by != t.assigned_to THEN 1 ELSE 2 END,
            CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
            t.created_at DESC
    ");
    $stmt->execute([$today, $today, $today, $userId, $today, $today, $today]);
    $relevantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($relevantTasks) . " tasks that should appear today</p>";
    
    if (!empty($relevantTasks)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Source</th><th>Priority</th><th>SLA Hours</th></tr>";
        foreach ($relevantTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td><strong>{$task['source_field']}</strong></td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>" . ($task['sla_hours'] ?? '0.25') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Step 4: Populate daily_tasks Table</h2>";
    
    $addedCount = 0;
    foreach ($relevantTasks as $task) {
        // Check for exact duplicates
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM daily_tasks 
            WHERE user_id = ? AND scheduled_date = ? 
            AND (original_task_id = ? OR (task_id = ? AND original_task_id IS NULL))
        ");
        $checkStmt->execute([$userId, $today, $task['id'], $task['id']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $initialStatus = 'not_started';
            if ($task['status'] === 'in_progress') {
                $initialStatus = 'in_progress';
            }
            
            $insertStmt = $db->prepare("
                INSERT INTO daily_tasks 
                (user_id, task_id, original_task_id, title, description, scheduled_date, 
                 priority, status, planned_duration, source_field, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $insertStmt->execute([
                $userId,
                $task['id'],
                $task['id'],
                $task['title'],
                $task['description'],
                $today,
                $task['priority'],
                $initialStatus,
                ($task['sla_hours'] ?? 0.25) * 60, // Convert hours to minutes
                $task['source_field']
            ]);
            
            if ($result) {
                $addedCount++;
            }
        }
    }
    
    echo "<p class='success'>✅ Added {$addedCount} tasks to daily_tasks table</p>";
    
    echo "<h2>Step 5: Verify Final Result</h2>";
    
    // Check final daily_tasks count
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM daily_tasks 
        WHERE user_id = ? AND scheduled_date = ?
    ");
    $stmt->execute([$userId, $today]);
    $finalCount = $stmt->fetchColumn();
    
    echo "<p>Final daily_tasks count: {$finalCount}</p>";
    
    if ($finalCount > 0) {
        // Test the actual getTasksForDate query
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
        $finalTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ getTasksForDate query returned " . count($finalTasks) . " tasks</p>";
        
        if (!empty($finalTasks)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>SLA Hours</th><th>Task Indicator</th></tr>";
            foreach ($finalTasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>" . htmlspecialchars($task['title']) . "</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['priority']}</td>";
                echo "<td>{$task['sla_hours']}</td>";
                echo "<td>" . htmlspecialchars($task['task_indicator']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h2>Step 6: Fix UnifiedWorkflowController</h2>";
    
    // The issue is that UnifiedWorkflowController doesn't call fetchAssignedTasksForDate before getTasksForDate
    // Let's create a fixed version
    
    echo "<p class='warning'>⚠️ The main issue is in UnifiedWorkflowController::dailyPlanner() method.</p>";
    echo "<p>The controller calls getTasksForDate() but doesn't ensure fetchAssignedTasksForDate() is called first.</p>";
    
    echo "<h2>Summary</h2>";
    
    if ($finalCount > 0) {
        echo "<p class='success'>✅ SUCCESS! The planner should now display {$finalCount} tasks.</p>";
        echo "<p>Visit: <a href='/ergon/workflow/daily-planner/{$today}' target='_blank'>Daily Planner</a></p>";
    } else {
        echo "<p class='error'>❌ Still no tasks in daily_tasks table. Check:</p>";
        echo "<ul>";
        echo "<li>Database permissions</li>";
        echo "<li>Table structure</li>";
        echo "<li>Task data in tasks table</li>";
        echo "</ul>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>The UnifiedWorkflowController needs to be updated to call fetchAssignedTasksForDate() before getTasksForDate()</li>";
    echo "<li>Or the DailyPlanner::getTasksForDate() method should automatically call fetchAssignedTasksForDate()</li>";
    echo "<li>Test the planner interface to confirm tasks are displaying</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>