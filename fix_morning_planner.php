<!DOCTYPE html>
<html>
<head>
    <title>Fix Morning Planner - Root Cause Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 15px; margin: 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Morning Planner Fix - Root Cause Analysis</h1>
        
        <?php
        session_start();
        
        echo "<div class='step'>";
        echo "<h3>Step 1: Check Session Status</h3>";
        
        if (!isset($_SESSION['user_id'])) {
            echo "<div class='error'>";
            echo "<strong>❌ ROOT CAUSE IDENTIFIED:</strong> No user session found!<br>";
            echo "You need to login first or use the test login for debugging.";
            echo "</div>";
            echo "<p><a href='/ergon/test_login.php' class='btn btn-success'>Setup Test Session</a></p>";
        } else {
            echo "<div class='success'>";
            echo "<strong>✅ Session OK:</strong> User ID = " . $_SESSION['user_id'];
            echo "</div>";
        }
        echo "</div>";
        
        if (isset($_SESSION['user_id'])) {
            echo "<div class='step'>";
            echo "<h3>Step 2: Check Database Connection & Tasks</h3>";
            
            try {
                require_once __DIR__ . '/app/config/database.php';
                $db = Database::connect();
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                echo "<div class='success'>✅ Database connection successful</div>";
                
                // Check if tables exist
                $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
                if ($stmt->rowCount() > 0) {
                    echo "<div class='success'>✅ daily_tasks table exists</div>";
                    
                    // Check for tasks
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
                    $stmt->execute([$userId, $today]);
                    $count = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($count['count'] > 0) {
                        echo "<div class='success'>✅ Found {$count['count']} tasks for today</div>";
                        
                        // Show tasks
                        $stmt = $db->prepare("SELECT id, title, priority, status, created_at FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY created_at DESC");
                        $stmt->execute([$userId, $today]);
                        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "<h4>Your Tasks for Today:</h4>";
                        echo "<ul>";
                        foreach ($tasks as $task) {
                            echo "<li>ID: {$task['id']} - <strong>{$task['title']}</strong> ({$task['priority']}) - {$task['created_at']}</li>";
                        }
                        echo "</ul>";
                        
                    } else {
                        echo "<div class='warning'>⚠️ No tasks found for today. Let's add some test data.</div>";
                        echo "<p><a href='/ergon/add_test_task.php' class='btn'>Add Test Tasks</a></p>";
                    }
                } else {
                    echo "<div class='error'>❌ daily_tasks table missing</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
            }
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<h3>Step 3: Test Morning Planner Controller</h3>";
            
            try {
                // Simulate the controller logic
                require_once __DIR__ . '/app/controllers/DailyWorkflowController.php';
                
                echo "<div class='success'>✅ Controller loaded successfully</div>";
                
                // Test the exact query used in morning planner
                $stmt = $db->prepare("
                    SELECT dt.*, d.name as department_name 
                    FROM daily_tasks dt 
                    LEFT JOIN departments d ON dt.department_id = d.id 
                    WHERE dt.assigned_to = ? AND dt.planned_date = ? 
                    ORDER BY dt.priority DESC, dt.created_at ASC
                ");
                $stmt->execute([$userId, $today]);
                $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='success'>✅ Morning planner query executed: " . count($todayPlans) . " results</div>";
                
                if (count($todayPlans) > 0) {
                    echo "<h4>Query Results:</h4>";
                    echo "<pre>";
                    foreach ($todayPlans as $plan) {
                        echo "ID: {$plan['id']}, Title: {$plan['title']}, Priority: {$plan['priority']}, Dept: " . ($plan['department_name'] ?? 'None') . "\n";
                    }
                    echo "</pre>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Controller test failed: " . $e->getMessage() . "</div>";
            }
            echo "</div>";
        }
        ?>
        
        <div class="step">
            <h3>Step 4: Test the Morning Planner Page</h3>
            <p>Now test the actual morning planner page:</p>
            <a href="/ergon/daily-workflow/morning-planner?debug=1" class="btn" target="_blank">Open Morning Planner (Debug Mode)</a>
            <a href="/ergon/daily-workflow/morning-planner" class="btn" target="_blank">Open Morning Planner (Normal)</a>
        </div>
        
        <div class="step">
            <h3>Quick Actions</h3>
            <a href="/ergon/test_login.php" class="btn btn-success">Setup Test Session</a>
            <a href="/ergon/add_test_task.php" class="btn">Add Test Tasks</a>
            <a href="/ergon/check_session_debug.php" class="btn">Check Session Details</a>
        </div>
        
        <div class="step">
            <h3>Summary</h3>
            <p><strong>Most Common Root Causes:</strong></p>
            <ol>
                <li><strong>Not logged in:</strong> Session doesn't have user_id set</li>
                <li><strong>No test data:</strong> No tasks added for today's date</li>
                <li><strong>Database issues:</strong> Tables missing or connection problems</li>
                <li><strong>Query issues:</strong> Controller query not returning expected results</li>
            </ol>
            
            <p><strong>Solution Steps:</strong></p>
            <ol>
                <li>Use test_login.php to establish a session</li>
                <li>Use add_test_task.php to add sample tasks</li>
                <li>Check morning planner with debug=1 parameter</li>
                <li>Verify tasks display correctly</li>
            </ol>
        </div>
    </div>
</body>
</html>