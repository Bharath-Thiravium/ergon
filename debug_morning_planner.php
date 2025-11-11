<!DOCTYPE html>
<html>
<head>
    <title>Morning Planner Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 8px 12px; margin: 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Morning Planner Debug Information</h1>
        
        <div class="debug-section">
            <h3>🔗 Quick Links</h3>
            <a href="http://localhost/ergon/add_test_task.php" class="btn">Test Tools</a>
            <a href="http://localhost/ergon/daily-workflow/morning-planner?debug=1" class="btn">Morning Planner (Debug)</a>
            <a href="http://localhost/ergon/daily-workflow/morning-planner" class="btn">Morning Planner (Normal)</a>
        </div>

        <div class="debug-section">
            <h3>📊 Debug Results</h3>
            <pre>
<?php
session_start();

// Simulate logged in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "✓ Database connection: OK\n";
    
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "User ID: $userId\n";
    echo "Today: $today\n";
    echo "Session Data: " . print_r($_SESSION, true) . "\n";
    
    // Check if daily_tasks table exists
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if ($stmt->rowCount() > 0) {
        echo "✓ daily_tasks table: EXISTS\n";
        
        // Check table structure
        echo "\nTable structure:\n";
        $stmt = $db->query("DESCRIBE daily_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check for existing tasks
        echo "\nChecking for existing tasks...\n";
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
        $stmt->execute([$userId, $today]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Tasks for today: {$count['count']}\n";
        
        // Get actual tasks
        $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId, $today]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nRecent tasks:\n";
        foreach ($tasks as $task) {
            echo "- ID: {$task['id']}, Title: {$task['title']}, Priority: {$task['priority']}, Created: {$task['created_at']}\n";
        }
        
        // Test the query used in morning planner
        echo "\nTesting morning planner query...\n";
        $stmt = $db->prepare("
            SELECT dt.*, d.name as department_name 
            FROM daily_tasks dt 
            LEFT JOIN departments d ON dt.department_id = d.id 
            WHERE dt.assigned_to = ? AND dt.planned_date = ? 
            ORDER BY dt.priority DESC, dt.created_at ASC
        ");
        $stmt->execute([$userId, $today]);
        $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Morning planner query result: " . count($todayPlans) . " tasks\n";
        foreach ($todayPlans as $plan) {
            echo "- {$plan['title']} (Priority: {$plan['priority']}, Dept: {$plan['department_name']})\n";
        }
        
    } else {
        echo "✗ daily_tasks table: MISSING\n";
    }
    
    // Check departments table
    echo "\nChecking departments table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'departments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ departments table: EXISTS\n";
        $stmt = $db->query("SELECT COUNT(*) as count FROM departments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total departments: {$count['count']}\n";
    } else {
        echo "✗ departments table: MISSING\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
            </pre>
        </div>

        <?php if (isset($todayPlans) && !empty($todayPlans)): ?>
        <div class="debug-section">
            <h3>📋 Tasks Table Preview</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Est. Hours</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayPlans as $task): ?>
                    <tr>
                        <td><?= $task['id'] ?></td>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= ucfirst($task['priority']) ?></td>
                        <td><?= $task['estimated_hours'] ?>h</td>
                        <td><?= ucfirst($task['status']) ?></td>
                        <td><?= date('H:i', strtotime($task['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="debug-section">
            <h3>🧪 Test Actions</h3>
            <p>Use the test tools to add sample data and verify the morning planner display:</p>
            <a href="http://localhost/ergon/add_test_task.php" class="btn">Go to Test Tools</a>
        </div>
    </div>
</body>
</html>