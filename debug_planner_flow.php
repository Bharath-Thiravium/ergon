<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

require_once __DIR__ . '/app/config/database.php';

echo "<h2>Planner Flow Debug</h2>";

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h3>1. Check Database Tables</h3>";
    
    // Check daily_tasks table
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if ($stmt->rowCount() > 0) {
        echo "✓ daily_tasks table exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE daily_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " ";
        }
        echo "<br><br>";
        
        // Check existing data
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
        $stmt->execute([$userId, $today]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Tasks for today: {$count['count']}<br>";
        
        if ($count['count'] > 0) {
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$userId, $today]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Recent Tasks:</h4>";
            foreach ($tasks as $task) {
                echo "ID: {$task['id']}, Title: {$task['title']}, Status: {$task['status']}, Created: {$task['created_at']}<br>";
            }
        }
    } else {
        echo "❌ daily_tasks table missing<br>";
    }
    
    echo "<h3>2. Test Form Submission</h3>";
    
    if ($_POST) {
        echo "Form submitted with data:<br>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        // Test insert
        if (isset($_POST['test_title'])) {
            $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
            $result = $stmt->execute([
                $_POST['test_title'],
                $_POST['test_description'] ?? '',
                $userId,
                $today,
                $_POST['test_priority'] ?? 'medium',
                floatval($_POST['test_hours'] ?? 1)
            ]);
            
            if ($result) {
                echo "✓ Task inserted successfully! ID: " . $db->lastInsertId() . "<br>";
            } else {
                echo "❌ Insert failed<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<form method="POST">
    <h3>3. Test Task Creation</h3>
    <input type="text" name="test_title" placeholder="Task Title" required><br><br>
    <textarea name="test_description" placeholder="Description"></textarea><br><br>
    <select name="test_priority">
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
    </select><br><br>
    <input type="number" name="test_hours" value="1" step="0.5"><br><br>
    <button type="submit">Create Test Task</button>
</form>

<p><a href="/ergon/daily-workflow/morning-planner?debug=1">Check Morning Planner</a></p>