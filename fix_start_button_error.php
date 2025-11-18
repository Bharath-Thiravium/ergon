<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Fix Start Button Error</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    // 1. Setup database structure
    echo "<h3>1. Setup Database</h3>";
    
    $columns = ['start_time' => 'TIMESTAMP NULL', 'sla_end_time' => 'TIMESTAMP NULL', 'active_seconds' => 'INT DEFAULT 0', 'total_pause_duration' => 'INT DEFAULT 0'];
    
    foreach ($columns as $column => $definition) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM daily_tasks LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN $column $definition");
                echo "<p class='success'>✅ Added $column</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ $column: " . $e->getMessage() . "</p>";
        }
    }
    
    $db->exec("CREATE TABLE IF NOT EXISTS sla_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        daily_task_id INT NOT NULL,
        action VARCHAR(20) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        duration_seconds INT DEFAULT 0,
        notes TEXT
    )");
    
    echo "<p class='success'>✅ Database setup complete</p>";
    
    // 2. Clear and recreate daily tasks
    echo "<h3>2. Create Daily Tasks</h3>";
    
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    
    // Get all active tasks for user
    $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' LIMIT 5");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tasks)) {
        // Create sample task
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, status, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['Sample Task for SLA Test', 'Test task for SLA functionality', $userId, $userId, 'assigned', 2]);
        
        $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed'");
        $stmt->execute([$userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create daily tasks
    foreach ($tasks as $task) {
        $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, priority, status, planned_duration, created_at) VALUES (?, ?, ?, ?, ?, ?, 'not_started', 120, NOW())");
        $stmt->execute([
            $userId, 
            $task['id'], 
            $today, 
            $task['title'], 
            $task['description'] ?? '', 
            $task['priority'] ?? 'medium'
        ]);
    }
    
    echo "<p class='success'>✅ Created " . count($tasks) . " daily tasks</p>";
    
    // 3. Test the start functionality
    echo "<h3>3. Test Start Function</h3>";
    
    $stmt = $db->prepare("SELECT id FROM daily_tasks WHERE user_id = ? AND scheduled_date = ? LIMIT 1");
    $stmt->execute([$userId, $today]);
    $testTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testTask) {
        echo "<p class='success'>✅ Test task ID: {$testTask['id']} ready for testing</p>";
        echo "<p>You can now test the start button in the Daily Planner</p>";
    } else {
        echo "<p class='error'>❌ No test task available</p>";
    }
    
    echo "<p><a href='/ergon/workflow/daily-planner' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>