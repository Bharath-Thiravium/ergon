<?php
/**
 * Debug Database Structure - Check Tables and Columns
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Mock for testing
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h1>Database Structure Analysis</h1>";
    echo "<style>table{border-collapse:collapse;width:100%;margin:10px 0;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>";
    
    // Check if tables exist
    $tables = ['tasks', 'daily_tasks', 'followups'];
    
    foreach ($tables as $table) {
        echo "<h2>Table: {$table}</h2>";
        
        // Check if table exists
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() == 0) {
            echo "<p style='color:red;'>❌ Table '{$table}' does not exist!</p>";
            continue;
        }
        
        echo "<p style='color:green;'>✅ Table '{$table}' exists</p>";
        
        // Get table structure
        $stmt = $db->prepare("DESCRIBE {$table}");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Columns:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get row count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table}");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p><strong>Row count:</strong> {$count}</p>";
        
        // Show sample data
        if ($count > 0) {
            echo "<h3>Sample Data (first 5 rows):</h3>";
            $stmt = $db->prepare("SELECT * FROM {$table} LIMIT 5");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                echo "<table>";
                echo "<tr>";
                foreach (array_keys($rows[0]) as $header) {
                    echo "<th>{$header}</th>";
                }
                echo "</tr>";
                
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "<hr>";
    }
    
    // Check specific relationships
    echo "<h2>Relationship Analysis</h2>";
    
    // Check tasks with planned_date = today
    $today = date('Y-m-d');
    echo "<h3>Tasks for Today ({$today})</h3>";
    
    $stmt = $db->prepare("
        SELECT id, title, planned_date, deadline, status, assigned_to, created_at
        FROM tasks 
        WHERE (planned_date = ? OR DATE(deadline) = ? OR DATE(created_at) = ?)
        AND status NOT IN ('completed', 'cancelled', 'deleted')
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$today, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayTasks)) {
        echo "<p style='color:orange;'>⚠️ No tasks found for today in tasks table</p>";
    } else {
        echo "<p style='color:green;'>✅ Found " . count($todayTasks) . " tasks for today</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Planned Date</th><th>Deadline</th><th>Status</th><th>Assigned To</th><th>Created</th></tr>";
        foreach ($todayTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>" . ($task['planned_date'] ?? 'NULL') . "</td>";
            echo "<td>" . ($task['deadline'] ?? 'NULL') . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['assigned_to']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check daily_tasks for today
    echo "<h3>Daily Tasks for Today ({$today})</h3>";
    
    $stmt = $db->prepare("
        SELECT id, user_id, task_id, original_task_id, title, scheduled_date, status, source_field, created_at
        FROM daily_tasks 
        WHERE scheduled_date = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$today]);
    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dailyTasks)) {
        echo "<p style='color:orange;'>⚠️ No daily tasks found for today</p>";
    } else {
        echo "<p style='color:green;'>✅ Found " . count($dailyTasks) . " daily tasks for today</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Task ID</th><th>Original Task ID</th><th>Title</th><th>Status</th><th>Source</th><th>Created</th></tr>";
        foreach ($dailyTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['user_id']}</td>";
            echo "<td>" . ($task['task_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($task['original_task_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>" . ($task['source_field'] ?? 'NULL') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($task['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for missing columns
    echo "<h2>Missing Columns Check</h2>";
    
    $requiredColumns = [
        'tasks' => ['planned_date', 'deadline', 'sla_hours', 'assigned_to', 'assigned_by'],
        'daily_tasks' => ['user_id', 'task_id', 'original_task_id', 'scheduled_date', 'source_field', 'rollover_source_date'],
        'followups' => ['task_id', 'user_id', 'follow_up_date', 'followup_type']
    ];
    
    foreach ($requiredColumns as $table => $columns) {
        echo "<h3>Required columns for {$table}:</h3>";
        
        $stmt = $db->prepare("DESCRIBE {$table}");
        $stmt->execute();
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        foreach ($columns as $col) {
            if (in_array($col, $existingColumns)) {
                echo "<p style='color:green;'>✅ {$col}</p>";
            } else {
                echo "<p style='color:red;'>❌ {$col} - MISSING!</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>