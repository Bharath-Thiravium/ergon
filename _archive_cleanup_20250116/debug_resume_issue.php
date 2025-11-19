<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

// Simulate user session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h2>Resume Task Debug Analysis</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    
    echo "<p><strong>User ID:</strong> $userId</p>";
    echo "<hr>";
    
    // 1. Check if daily_tasks table exists and has required columns
    echo "<h3>1. Database Structure Check</h3>";
    
    try {
        $stmt = $db->query("DESCRIBE daily_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $requiredColumns = ['id', 'user_id', 'status', 'resume_time', 'updated_at'];
        $existingColumns = array_column($columns, 'Field');
        
        echo "<p class='success'>✅ daily_tasks table exists</p>";
        echo "<p><strong>Existing columns:</strong> " . implode(', ', $existingColumns) . "</p>";
        
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        if (!empty($missingColumns)) {
            echo "<p class='error'>❌ Missing required columns: " . implode(', ', $missingColumns) . "</p>";
        } else {
            echo "<p class='success'>✅ All required columns exist</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ daily_tasks table error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 2. Check for tasks that can be resumed (status = 'paused' or 'on_break')
    echo "<h3>2. Tasks Available for Resume</h3>";
    
    try {
        $stmt = $db->prepare("
            SELECT id, title, status, start_time, pause_time, resume_time, active_seconds 
            FROM daily_tasks 
            WHERE user_id = ? AND status IN ('paused', 'on_break')
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$userId]);
        $pausedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pausedTasks)) {
            echo "<p class='warning'>⚠ No paused tasks found for user $userId</p>";
            
            // Check all tasks for this user
            $stmt = $db->prepare("SELECT id, title, status FROM daily_tasks WHERE user_id = ?");
            $stmt->execute([$userId]);
            $allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($allTasks)) {
                echo "<p class='info'>ℹ No daily tasks exist for this user</p>";
            } else {
                echo "<p class='info'>ℹ Available tasks with statuses:</p>";
                foreach ($allTasks as $task) {
                    echo "<p>- Task #{$task['id']}: {$task['title']} (Status: {$task['status']})</p>";
                }
            }
        } else {
            echo "<p class='success'>✅ Found " . count($pausedTasks) . " paused tasks</p>";
            foreach ($pausedTasks as $task) {
                echo "<p>- Task #{$task['id']}: {$task['title']} (Status: {$task['status']})</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking paused tasks: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    
    // 3. Test the resumeTask method directly
    echo "<h3>3. Testing resumeTask Method</h3>";
    
    // Create a test task if none exist
    $testTaskId = null;
    try {
        // First, try to find an existing paused task
        $stmt = $db->prepare("SELECT id FROM daily_tasks WHERE user_id = ? AND status IN ('paused', 'on_break') LIMIT 1");
        $stmt->execute([$userId]);
        $existingTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingTask) {
            $testTaskId = $existingTask['id'];
            echo "<p class='info'>ℹ Using existing paused task ID: $testTaskId</p>";
        } else {
            // Create a test task
            $stmt = $db->prepare("
                INSERT INTO daily_tasks 
                (user_id, title, description, scheduled_date, status, created_at) 
                VALUES (?, 'Test Resume Task', 'Test task for resume functionality', ?, 'paused', NOW())
            ");
            $stmt->execute([$userId, date('Y-m-d')]);
            $testTaskId = $db->lastInsertId();
            echo "<p class='info'>ℹ Created test task ID: $testTaskId</p>";
        }
        
        // Now test the resumeTask method
        $planner = new DailyPlanner();
        $result = $planner->resumeTask($testTaskId, $userId);
        
        if ($result) {
            echo "<p class='success'>✅ resumeTask method executed successfully</p>";
            
            // Verify the task status was updated
            $stmt = $db->prepare("SELECT status, resume_time FROM daily_tasks WHERE id = ?");
            $stmt->execute([$testTaskId]);
            $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updatedTask) {
                echo "<p class='success'>✅ Task status updated to: {$updatedTask['status']}</p>";
                echo "<p class='success'>✅ Resume time set to: {$updatedTask['resume_time']}</p>";
            }
        } else {
            echo "<p class='error'>❌ resumeTask method failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error testing resumeTask: " . $e->getMessage() . "</p>";
        echo "<p class='error'>Stack trace: " . $e->getTraceAsString() . "</p>";
    }
    
    echo "<hr>";
    
    // 4. Test the API endpoint
    echo "<h3>4. Testing API Endpoint</h3>";
    
    if ($testTaskId) {
        echo "<p class='info'>ℹ Testing API call simulation...</p>";
        
        // Simulate the API call
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['action'] = 'resume';
        
        $input = json_encode(['task_id' => $testTaskId]);
        
        // Capture output
        ob_start();
        
        try {
            // Simulate the API logic
            $planner = new DailyPlanner();
            $result = $planner->resumeTask($testTaskId, $userId);
            $response = ['success' => $result, 'message' => $result ? 'Task resumed' : 'Failed to resume task'];
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        $apiOutput = ob_get_clean();
        echo "<p><strong>API Response:</strong> <code>$apiOutput</code></p>";
        
        $apiResponse = json_decode($apiOutput, true);
        if ($apiResponse && $apiResponse['success']) {
            echo "<p class='success'>✅ API endpoint working correctly</p>";
        } else {
            echo "<p class='error'>❌ API endpoint failed: " . ($apiResponse['message'] ?? 'Unknown error') . "</p>";
        }
    }
    
    echo "<hr>";
    
    // 5. Check for common issues
    echo "<h3>5. Common Issues Check</h3>";
    
    // Check if time_logs table exists (used by logTimeAction)
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'time_logs'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ time_logs table exists</p>";
        } else {
            echo "<p class='warning'>⚠ time_logs table missing - this might cause issues</p>";
            echo "<p class='info'>ℹ Creating time_logs table...</p>";
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS time_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    daily_task_id INT NOT NULL,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    timestamp TIMESTAMP NOT NULL,
                    active_duration INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_daily_task_id (daily_task_id),
                    INDEX idx_user_id (user_id)
                )
            ");
            echo "<p class='success'>✅ time_logs table created</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking time_logs table: " . $e->getMessage() . "</p>";
    }
    
    // Check database connection
    try {
        $stmt = $db->query("SELECT 1");
        echo "<p class='success'>✅ Database connection is working</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database connection issue: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>6. Recommendations</h3>";
    echo "<p>If the resume functionality is still failing:</p>";
    echo "<ol>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify the task ID being passed to the API</li>";
    echo "<li>Check if the task status is actually 'paused' or 'on_break'</li>";
    echo "<li>Ensure the user has permission to resume the task</li>";
    echo "<li>Check server error logs for detailed error messages</li>";
    echo "</ol>";
    
    echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>Back to Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Critical error: " . $e->getMessage() . "</p>";
}
?>