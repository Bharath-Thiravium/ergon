<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Daily Planner Database Fix</h2>";
    echo "<hr>";
    
    // 1. Ensure tasks table has required columns
    echo "<h3>1. Checking tasks table structure...</h3>";
    
    $requiredColumns = [
        'assigned_at' => 'TIMESTAMP NULL DEFAULT NULL',
        'planned_date' => 'DATE NULL DEFAULT NULL'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM tasks LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                echo "<p>Adding missing column: <strong>$column</strong></p>";
                $db->exec("ALTER TABLE tasks ADD COLUMN $column $definition");
                echo "<p style='color: green;'>✅ Added column $column</p>";
            } else {
                echo "<p>✅ Column <strong>$column</strong> exists</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error with column $column: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Ensure daily_tasks table exists with correct structure
    echo "<h3>2. Checking daily_tasks table...</h3>";
    
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
        if ($stmt->rowCount() == 0) {
            echo "<p>Creating daily_tasks table...</p>";
            
            $createSQL = "CREATE TABLE daily_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                scheduled_date DATE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                planned_start_time TIME NULL,
                planned_duration INT DEFAULT 60,
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(50) DEFAULT 'not_started',
                start_time TIMESTAMP NULL,
                pause_time TIMESTAMP NULL,
                resume_time TIMESTAMP NULL,
                completion_time TIMESTAMP NULL,
                active_seconds INT DEFAULT 0,
                completed_percentage INT DEFAULT 0,
                postponed_from_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, scheduled_date),
                INDEX idx_status (status)
            )";
            
            $db->exec($createSQL);
            echo "<p style='color: green;'>✅ Created daily_tasks table</p>";
        } else {
            echo "<p>✅ daily_tasks table exists</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error with daily_tasks table: " . $e->getMessage() . "</p>";
    }
    
    // 3. Add sample data if tables are empty
    echo "<h3>3. Adding sample data...</h3>";
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = 1");
    $stmt->execute();
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount == 0) {
        echo "<p>Adding sample tasks...</p>";
        
        $today = date('Y-m-d');
        $sampleTasks = [
            [
                'title' => 'Review Client Proposal',
                'description' => 'Review the new client proposal document',
                'assigned_by' => 2,
                'assigned_to' => 1,
                'priority' => 'high',
                'status' => 'assigned',
                'deadline' => $today,
                'planned_date' => $today
            ],
            [
                'title' => 'Update Project Documentation',
                'description' => 'Update documentation with latest changes',
                'assigned_by' => 1,
                'assigned_to' => 1,
                'priority' => 'medium',
                'status' => 'assigned',
                'deadline' => $today,
                'planned_date' => $today
            ],
            [
                'title' => 'Team Meeting Preparation',
                'description' => 'Prepare agenda for team meeting',
                'assigned_by' => 3,
                'assigned_to' => 1,
                'priority' => 'medium',
                'status' => 'assigned',
                'deadline' => $today,
                'planned_date' => null
            ]
        ];
        
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, status, deadline, planned_date, created_at, assigned_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($sampleTasks as $task) {
            $stmt->execute([
                $task['title'],
                $task['description'],
                $task['assigned_by'],
                $task['assigned_to'],
                $task['priority'],
                $task['status'],
                $task['deadline'],
                $task['planned_date']
            ]);
        }
        
        echo "<p style='color: green;'>✅ Added " . count($sampleTasks) . " sample tasks</p>";
    } else {
        echo "<p>✅ Tasks already exist ($taskCount tasks)</p>";
    }
    
    // 4. Test the query
    echo "<h3>4. Testing daily planner query...</h3>";
    
    $userId = 1;
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT * FROM tasks 
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
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query returned: <strong>" . count($tasks) . " tasks</strong></p>";
    
    if (count($tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Title</th><th>Source</th><th>Priority</th><th>Status</th></tr>";
        foreach ($tasks as $task) {
            $source = ($task['assigned_by'] != $task['assigned_to']) ? 'From Others' : 'Self';
            echo "<tr>";
            echo "<td>{$task['title']}</td>";
            echo "<td>$source</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Database setup complete and working!</p>";
    } else {
        echo "<p style='color: red;'>❌ No tasks returned by query</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<p><a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Daily Planner</a></p>";
    echo "<p><a href='debug_planner_issue.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Run Debug Script</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; }
</style>