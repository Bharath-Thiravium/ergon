<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Form Submission Debug</h2>";

if ($_POST) {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    try {
        require_once __DIR__ . '/app/config/database.php';
        $db = Database::connect();
        
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        echo "<h3>Database Test:</h3>";
        echo "User ID: $userId<br>";
        echo "Today: $today<br>";
        
        // Test the exact insert query from controller
        if (isset($_POST['plans']) && is_array($_POST['plans'])) {
            foreach ($_POST['plans'] as $plan) {
                if (!empty($plan['title'])) {
                    echo "<h4>Inserting Task: " . htmlspecialchars($plan['title']) . "</h4>";
                    
                    $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, department_id, task_category, company_name, contact_person, contact_phone, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', ?, ?, ?, ?, ?, NOW())");
                    
                    $params = [
                        trim($plan['title']),
                        trim($plan['description'] ?? ''),
                        $userId,
                        $today,
                        $plan['priority'] ?? 'medium',
                        floatval($plan['estimated_hours'] ?? 1),
                        !empty($plan['department_id']) ? intval($plan['department_id']) : null,
                        !empty($plan['task_category']) ? trim($plan['task_category']) : null,
                        !empty($plan['company_name']) ? trim($plan['company_name']) : null,
                        !empty($plan['contact_person']) ? trim($plan['contact_person']) : null,
                        !empty($plan['contact_phone']) ? trim($plan['contact_phone']) : null
                    ];
                    
                    echo "Parameters: <pre>" . print_r($params, true) . "</pre>";
                    
                    $result = $stmt->execute($params);
                    
                    if ($result) {
                        $taskId = $db->lastInsertId();
                        echo "<span style='color: green;'>✓ SUCCESS: Task inserted with ID: $taskId</span><br>";
                    } else {
                        echo "<span style='color: red;'>✗ FAILED: Insert failed</span><br>";
                        echo "Error Info: <pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>ERROR: " . $e->getMessage() . "</span><br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>No POST data received. Submit the form below to test:</p>";
}
?>

<form method="POST">
    <h3>Test Form</h3>
    <input type="text" name="plans[0][title]" placeholder="Task Title" required><br><br>
    <textarea name="plans[0][description]" placeholder="Description"></textarea><br><br>
    <select name="plans[0][priority]">
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
    </select><br><br>
    <input type="number" name="plans[0][estimated_hours]" value="1" step="0.5"><br><br>
    <button type="submit">Test Submit</button>
</form>

<p><a href="/ergon/daily-workflow/morning-planner">Back to Morning Planner</a></p>