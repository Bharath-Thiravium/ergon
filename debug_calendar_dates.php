<?php
// Debug script to check calendar date handling
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Calendar Date Debug</h2>";
    
    // Check current timezone settings
    echo "<h3>Timezone Settings:</h3>";
    echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
    
    $stmt = $db->query("SELECT @@session.time_zone as mysql_timezone");
    $result = $stmt->fetch();
    echo "MySQL Timezone: " . $result['mysql_timezone'] . "<br>";
    
    echo "Current PHP Date: " . date('Y-m-d H:i:s') . "<br>";
    
    $stmt = $db->query("SELECT NOW() as mysql_now");
    $result = $stmt->fetch();
    echo "Current MySQL Date: " . $result['mysql_now'] . "<br><br>";
    
    // Check sample tasks with planned_date
    echo "<h3>Sample Tasks with Planned Date:</h3>";
    $stmt = $db->query("SELECT id, title, planned_date, deadline, created_at FROM tasks WHERE planned_date IS NOT NULL ORDER BY planned_date DESC LIMIT 10");
    $tasks = $stmt->fetchAll();
    
    if (empty($tasks)) {
        echo "No tasks with planned_date found.<br>";
        
        // Create a test task for today
        echo "<h4>Creating test task for today...</h4>";
        $today = date('Y-m-d');
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, planned_date, status, created_at) VALUES (?, ?, 1, 1, ?, 'assigned', NOW())");
        $stmt->execute(['Test Calendar Task - Today', 'Test task to verify calendar date display', $today]);
        
        echo "Test task created with planned_date: " . $today . "<br>";
        
        // Fetch the created task
        $stmt = $db->query("SELECT id, title, planned_date, deadline, created_at FROM tasks WHERE title LIKE 'Test Calendar Task%' ORDER BY created_at DESC LIMIT 1");
        $tasks = $stmt->fetchAll();
    }
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Planned Date</th><th>Deadline</th><th>Created At</th></tr>";
    
    foreach ($tasks as $task) {
        echo "<tr>";
        echo "<td>" . $task['id'] . "</td>";
        echo "<td>" . htmlspecialchars($task['title']) . "</td>";
        echo "<td>" . ($task['planned_date'] ?? 'NULL') . "</td>";
        echo "<td>" . ($task['deadline'] ?? 'NULL') . "</td>";
        echo "<td>" . $task['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Test JavaScript date conversion
    echo "<h3>JavaScript Date Conversion Test:</h3>";
    echo "<script>";
    echo "console.log('Testing date conversion...');";
    echo "const testDate = new Date();";
    echo "const dateStr = testDate.toISOString().split('T')[0];";
    echo "console.log('Today as ISO string:', dateStr);";
    echo "document.write('Today as ISO string: ' + dateStr + '<br>');";
    
    echo "const testTaskDate = '" . ($tasks[0]['planned_date'] ?? date('Y-m-d')) . "';";
    echo "console.log('Task planned_date:', testTaskDate);";
    echo "document.write('Task planned_date: ' + testTaskDate + '<br>');";
    
    echo "const taskDateStr = testTaskDate.split(' ')[0];";
    echo "console.log('Task date (date part only):', taskDateStr);";
    echo "document.write('Task date (date part only): ' + taskDateStr + '<br>');";
    
    echo "const matches = (dateStr === taskDateStr);";
    echo "console.log('Dates match:', matches);";
    echo "document.write('Dates match: ' + matches + '<br>');";
    echo "</script>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>