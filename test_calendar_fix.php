<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Set user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['role'] = 'admin';
}

try {
    $db = Database::connect();
    
    // Ensure tasks table has planned_date column
    $stmt = $db->query("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL");
        echo "Added planned_date column to tasks table.<br>";
    }
    
    // Create test tasks for different dates
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Clear existing test tasks
    $db->exec("DELETE FROM tasks WHERE title LIKE 'Calendar Test Task%'");
    
    // Insert test tasks
    $testTasks = [
        ['Calendar Test Task - Today', $today],
        ['Calendar Test Task - Tomorrow', $tomorrow],
        ['Calendar Test Task - Yesterday', $yesterday]
    ];
    
    $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, planned_date, status, created_at) VALUES (?, 'Test task for calendar verification', 1, 1, ?, 'assigned', NOW())");
    
    foreach ($testTasks as $task) {
        $stmt->execute($task);
    }
    
    echo "<h2>Test Tasks Created:</h2>";
    echo "Today: $today<br>";
    echo "Tomorrow: $tomorrow<br>";
    echo "Yesterday: $yesterday<br><br>";
    
    // Fetch tasks for calendar
    $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? AND (t.deadline IS NOT NULL OR t.due_date IS NOT NULL OR t.planned_date IS NOT NULL) ORDER BY COALESCE(t.planned_date, t.deadline, t.due_date) ASC");
    $stmt->execute([1]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tasks Retrieved for Calendar:</h3>";
    echo "<pre>" . json_encode($tasks, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>Calendar Test:</h3>";
    echo "<div id='calendar-test'></div>";
    
    echo "<script>";
    echo "const tasks = " . json_encode($tasks) . ";";
    echo "const today = new Date();";
    echo "const todayStr = today.toISOString().split('T')[0];";
    echo "console.log('Today string:', todayStr);";
    
    echo "const todayTasks = tasks.filter(task => {";
    echo "    const taskDate = task.planned_date || task.deadline || task.due_date;";
    echo "    if (!taskDate) return false;";
    echo "    const taskDateStr = taskDate.split(' ')[0];";
    echo "    console.log('Checking task:', task.title, 'Date:', taskDateStr, 'Today:', todayStr, 'Match:', taskDateStr === todayStr);";
    echo "    return taskDateStr === todayStr;";
    echo "});";
    
    echo "console.log('Tasks for today:', todayTasks);";
    echo "document.getElementById('calendar-test').innerHTML = '<p>Tasks for today: ' + todayTasks.length + '</p>';";
    echo "todayTasks.forEach(task => {";
    echo "    document.getElementById('calendar-test').innerHTML += '<div>- ' + task.title + ' (Planned: ' + task.planned_date + ')</div>';";
    echo "});";
    echo "</script>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<a href="/ergon/tasks/calendar">Go to Calendar View</a>