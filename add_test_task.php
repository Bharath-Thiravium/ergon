<!DOCTYPE html>
<html>
<head>
    <title>Morning Planner Test Tools</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 15px; margin: 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn:hover { background: #005a87; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; }
        .result { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Morning Planner Test Tools</h1>
        
        <?php
        session_start();
        
        // Simulate logged in user for testing
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'test_user';
            $_SESSION['role'] = 'user';
        }
        
        if (isset($_POST['action'])) {
            require_once __DIR__ . '/app/config/database.php';
            
            try {
                $db = Database::connect();
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                if ($_POST['action'] === 'add_single') {
                    $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
                    $result = $stmt->execute([
                        'Test Task ' . time(),
                        'Single test task to verify display functionality',
                        $userId, $today, 'medium', 2.0
                    ]);
                    
                    if ($result) {
                        echo '<div class="result success">✓ Single test task added successfully! ID: ' . $db->lastInsertId() . '</div>';
                    } else {
                        echo '<div class="result error">✗ Failed to add test task</div>';
                    }
                    
                } elseif ($_POST['action'] === 'add_multiple') {
                    $tasks = [
                        ['Morning Meeting', 'Daily standup with team', 'high', 1.0],
                        ['Code Review', 'Review pull requests from yesterday', 'medium', 2.5],
                        ['Client Call', 'Discuss project requirements', 'urgent', 1.5],
                        ['Documentation', 'Update API documentation', 'low', 3.0]
                    ];
                    
                    $count = 0;
                    foreach ($tasks as $task) {
                        $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
                        if ($stmt->execute([$task[0], $task[1], $userId, $today, $task[2], $task[3]])) {
                            $count++;
                        }
                    }
                    echo '<div class="result success">✓ Added ' . $count . ' test tasks successfully!</div>';
                    
                } elseif ($_POST['action'] === 'clear_all') {
                    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
                    $result = $stmt->execute([$userId, $today]);
                    echo '<div class="result info">🗑️ Cleared all tasks for today</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="result error">Error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
        
        <div class="test-section">
            <h3>📝 Add Test Tasks</h3>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="add_single">
                <button type="submit" class="btn btn-success">Add Single Task</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="add_multiple">
                <button type="submit" class="btn btn-success">Add Multiple Tasks</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Clear all tasks for today?')">Clear All Tasks</button>
            </form>
        </div>
        
        <div class="test-section">
            <h3>🔍 View Morning Planner</h3>
            <a href="http://localhost/ergon/daily-workflow/morning-planner" class="btn" target="_blank">Normal View</a>
            <a href="http://localhost/ergon/daily-workflow/morning-planner?debug=1" class="btn btn-warning" target="_blank">Debug View</a>
        </div>
        
        <div class="test-section">
            <h3>🛠️ Other Tools</h3>
            <a href="http://localhost/ergon/debug_morning_planner.php" class="btn" target="_blank">Database Debug Info</a>
            <a href="http://localhost/ergon/test_planner_display.html" class="btn" target="_blank">AJAX Test Interface</a>
        </div>
        
        <div class="test-section">
            <h3>📊 Current Status</h3>
            <?php
            try {
                require_once __DIR__ . '/app/config/database.php';
                $db = Database::connect();
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
                $stmt->execute([$userId, $today]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<p><strong>Tasks for today:</strong> ' . $count['count'] . '</p>';
                echo '<p><strong>User ID:</strong> ' . $userId . '</p>';
                echo '<p><strong>Date:</strong> ' . $today . '</p>';
                
            } catch (Exception $e) {
                echo '<p class="error">Error getting status: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>