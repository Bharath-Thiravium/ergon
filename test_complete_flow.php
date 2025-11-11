<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Complete Flow Test</h2>";

if ($_POST) {
    echo "<h3>Testing Form Submission...</h3>";
    
    try {
        require_once __DIR__ . '/app/config/database.php';
        $db = Database::connect();
        
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        // Test insert
        $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
        $result = $stmt->execute([
            $_POST['title'],
            $_POST['description'] ?? '',
            $userId,
            $today,
            $_POST['priority'] ?? 'medium',
            floatval($_POST['estimated_hours'] ?? 1)
        ]);
        
        if ($result) {
            $taskId = $db->lastInsertId();
            echo "<p style='color: green;'>✓ Task saved successfully! ID: $taskId</p>";
            
            // Test retrieval
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY created_at DESC");
            $stmt->execute([$userId, $today]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Retrieved Tasks:</h3>";
            if (!empty($tasks)) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Priority</th><th>Hours</th><th>Status</th><th>Created</th></tr>";
                foreach ($tasks as $task) {
                    echo "<tr>";
                    echo "<td>{$task['id']}</td>";
                    echo "<td>{$task['title']}</td>";
                    echo "<td>{$task['description']}</td>";
                    echo "<td>{$task['priority']}</td>";
                    echo "<td>{$task['estimated_hours']}</td>";
                    echo "<td>{$task['status']}</td>";
                    echo "<td>{$task['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p style='color: green;'>✓ Tasks display correctly!</p>";
            } else {
                echo "<p style='color: red;'>❌ No tasks found after insert</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to save task</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Fill out the form below to test the complete save and display flow:</p>";
}
?>

<form method="POST">
    <h3>Test Task Form</h3>
    <label>Title:</label><br>
    <input type="text" name="title" value="Test Task <?= time() ?>" required><br><br>
    
    <label>Description:</label><br>
    <textarea name="description">Test description for morning planner</textarea><br><br>
    
    <label>Priority:</label><br>
    <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
    </select><br><br>
    
    <label>Estimated Hours:</label><br>
    <input type="number" name="estimated_hours" value="2" step="0.5"><br><br>
    
    <button type="submit">Test Save & Display</button>
</form>

<p><a href="/ergon/daily-workflow/morning-planner">Go to Morning Planner</a></p>