<?php
/**
 * Debug Script for Start Button Issues
 * This script will help identify why the Start button isn't working
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';

// Start session for debugging
session_start();

echo "<h1>Daily Planner Start Button Debug</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .error{color:red;} .success{color:green;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

try {
    $db = Database::connect();
    echo "<div class='success'>✓ Database connection successful</div>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='error'>✗ User not logged in. Please log in first.</div>";
        echo "<a href='/ergon/login'>Go to Login</a>";
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    echo "<div class='info'>Current User ID: {$userId}</div>";
    
    // 1. Check daily_tasks table structure
    echo "<h2>1. Table Structure Check</h2>";
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>daily_tasks columns:</h3><pre>";
        foreach ($columns as $column) {
            echo "{$column['Field']}: {$column['Type']} " . 
                 ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
                 ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
        }
        echo "</pre>";
        
        // Check for required columns
        $requiredColumns = ['sla_end_time', 'total_pause_duration', 'active_seconds'];
        $existingColumns = array_column($columns, 'Field');
        
        foreach ($requiredColumns as $reqCol) {
            if (in_array($reqCol, $existingColumns)) {
                echo "<div class='success'>✓ Required column '{$reqCol}' exists</div>";
            } else {
                echo "<div class='error'>✗ Missing required column '{$reqCol}'</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ Table structure check failed: " . $e->getMessage() . "</div>";
    }
    
    // 2. Check for tasks
    echo "<h2>2. Current Tasks Check</h2>";
    try {
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = CURDATE() LIMIT 5");
        $stmt->execute([$userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tasks)) {
            echo "<div class='error'>✗ No tasks found for today</div>";
            echo "<p>Creating test task...</p>";
            
            // Create a test task
            $stmt = $db->prepare("
                INSERT INTO daily_tasks (user_id, scheduled_date, title, description, priority, status, created_at)
                VALUES (?, CURDATE(), 'Test Task for Debug', 'This is a test task to debug the start button', 'medium', 'not_started', NOW())
            ");
            $stmt->execute([$userId]);
            echo "<div class='success'>✓ Test task created</div>";
            
            // Re-fetch tasks
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = CURDATE() LIMIT 5");
            $stmt->execute([$userId]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo "<h3>Current tasks:</h3><pre>";
        foreach ($tasks as $task) {
            echo "ID: {$task['id']}, Title: {$task['title']}, Status: {$task['status']}, Start Time: " . ($task['start_time'] ?? 'NULL') . "\n";
        }
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ Tasks check failed: " . $e->getMessage() . "</div>";
    }
    
    // 3. Test API endpoint
    echo "<h2>3. API Endpoint Test</h2>";
    if (!empty($tasks)) {
        $testTaskId = $tasks[0]['id'];
        echo "<p>Testing with Task ID: {$testTaskId}</p>";
        
        // Simulate API call
        try {
            $now = date('Y-m-d H:i:s');
            
            // Get task and SLA info
            $stmt = $db->prepare("SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours FROM daily_tasks dt LEFT JOIN tasks t ON dt.task_id = t.id WHERE dt.id = ?");
            $stmt->execute([$testTaskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                echo "<div class='success'>✓ Task found for API test</div>";
                echo "<pre>Task data: " . json_encode($task, JSON_PRETTY_PRINT) . "</pre>";
                
                if ($task['status'] === 'not_started') {
                    // Calculate SLA end time
                    $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $task['sla_hours'] . ' hours'));
                    
                    // Try to start the task
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = ?, sla_end_time = ? WHERE id = ?");
                    $result = $stmt->execute([$now, $slaEndTime, $testTaskId]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        echo "<div class='success'>✓ Task start simulation successful</div>";
                        echo "<div class='info'>Start Time: {$now}</div>";
                        echo "<div class='info'>SLA End Time: {$slaEndTime}</div>";
                        
                        // Verify the update
                        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ?");
                        $stmt->execute([$testTaskId]);
                        $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo "<h3>Updated task data:</h3><pre>";
                        echo json_encode($updatedTask, JSON_PRETTY_PRINT);
                        echo "</pre>";
                        
                    } else {
                        echo "<div class='error'>✗ Task start simulation failed</div>";
                        echo "<div class='error'>Rows affected: " . $stmt->rowCount() . "</div>";
                    }
                } else {
                    echo "<div class='info'>Task status is '{$task['status']}' - not 'not_started'</div>";
                }
            } else {
                echo "<div class='error'>✗ Task not found for API test</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>✗ API simulation failed: " . $e->getMessage() . "</div>";
        }
    }
    
    // 4. Check SLA history table
    echo "<h2>4. SLA History Table Check</h2>";
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'sla_history'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "<div class='success'>✓ SLA history table exists</div>";
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM sla_history");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "<div class='info'>SLA history records: {$count}</div>";
        } else {
            echo "<div class='error'>✗ SLA history table missing</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ SLA history check failed: " . $e->getMessage() . "</div>";
    }
    
    // 5. JavaScript/Frontend test
    echo "<h2>5. Frontend Integration Test</h2>";
    echo "<p>Click the button below to test the actual API call:</p>";
    
    if (!empty($tasks)) {
        $testTaskId = $tasks[0]['id'];
        echo "<button onclick='testStartTask({$testTaskId})'>Test Start Task {$testTaskId}</button>";
        echo "<div id='testResult'></div>";
        
        echo "<script>
        function testStartTask(taskId) {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            fetch('/ergon/api/daily_planner_workflow.php?action=start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: parseInt(taskId) })
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                if (data.success) {
                    resultDiv.style.color = 'green';
                } else {
                    resultDiv.style.color = 'red';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p style=\"color:red\">Error: ' + error.message + '</p>';
            });
        }
        </script>";
    }
    
    echo "<h2>6. Recommendations</h2>";
    echo "<div class='info'>";
    echo "<p><strong>If you see any errors above, run the database fix script:</strong></p>";
    echo "<p><a href='/ergon/database_fix_daily_tasks.php' target='_blank'>Run Database Fix Script</a></p>";
    echo "<p><strong>Common issues and solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Missing columns: Run the database fix script</li>";
    echo "<li>No tasks: Go to Daily Planner and click 'Sync Tasks'</li>";
    echo "<li>API errors: Check error logs in browser console</li>";
    echo "<li>Status not persisting: Database transaction issues</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Debug script failed: " . $e->getMessage() . "</div>";
}
?>