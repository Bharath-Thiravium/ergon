<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Follow-up Creation Test</h2>";
    
    // Test 1: Check if followups table exists and has correct structure
    echo "<h3>1. Table Structure Check</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ followups table does not exist</p>";
        exit;
    }
    echo "<p>✅ followups table exists</p>";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE followups");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Table columns:</p><ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    // Test 2: Create a test task with Follow-up category
    echo "<h3>2. Create Test Task with Follow-up Category</h3>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, status, progress, sla_hours, department_id, task_category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([
            'Test Follow-up Task',
            'This is a test task to verify follow-up creation',
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            'ad-hoc',
            'medium',
            date('Y-m-d', strtotime('+3 days')),
            'assigned',
            0,
            24,
            1, // Assuming department ID 1 exists
            'Follow-up'
        ]);
        
        if ($result) {
            $taskId = $db->lastInsertId();
            echo "<p>✅ Test task created with ID: $taskId</p>";
            
            // Manually trigger follow-up creation
            $taskData = [
                'title' => 'Test Follow-up Task',
                'description' => 'This is a test task to verify follow-up creation',
                'assigned_to' => $_SESSION['user_id'],
                'assigned_by' => $_SESSION['user_id'],
                'task_category' => 'Follow-up',
                'deadline' => date('Y-m-d', strtotime('+3 days'))
            ];
            
            $postData = [
                'company_name' => 'Test Company',
                'contact_person' => 'John Doe',
                'contact_phone' => '123-456-7890',
                'project_name' => 'Test Project',
                'followup_date' => date('Y-m-d', strtotime('+1 day')),
                'followup_time' => '10:00'
            ];
            
            // Create followup
            try {
                $followupDate = $postData['followup_date'];
                $followupTime = $postData['followup_time'] . ':00';
                
                $stmt = $db->prepare("
                    INSERT INTO followups (
                        user_id, title, description, company_name, contact_person, 
                        contact_phone, project_name, follow_up_date, reminder_time, 
                        original_date, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $followupResult = $stmt->execute([
                    $taskData['assigned_to'],
                    'Follow-up: ' . $postData['company_name'] . ' - ' . $taskData['title'],
                    'Auto-created follow-up for task: ' . $taskData['description'],
                    $postData['company_name'],
                    $postData['contact_person'],
                    $postData['contact_phone'],
                    $postData['project_name'],
                    $followupDate,
                    $followupTime,
                    $followupDate
                ]);
                
                if ($followupResult) {
                    $followupId = $db->lastInsertId();
                    echo "<p>✅ Follow-up created with ID: $followupId</p>";
                } else {
                    echo "<p>❌ Failed to create follow-up: " . implode(', ', $stmt->errorInfo()) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Follow-up creation error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ Failed to create test task</p>";
        }
    }
    
    // Test 3: Check existing follow-ups
    echo "<h3>3. Current Follow-ups for User</h3>";
    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$_SESSION['user_id']]);
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($followups)) {
        echo "<p>No follow-ups found for current user</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Company</th><th>Contact</th><th>Date</th><th>Status</th><th>Created</th></tr>";
        foreach ($followups as $f) {
            echo "<tr>";
            echo "<td>{$f['id']}</td>";
            echo "<td>" . htmlspecialchars($f['title']) . "</td>";
            echo "<td>" . htmlspecialchars($f['company_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($f['contact_person'] ?? '') . "</td>";
            echo "<td>{$f['follow_up_date']}</td>";
            echo "<td>{$f['status']}</td>";
            echo "<td>{$f['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 4: Check recent tasks with follow-up category
    echo "<h3>4. Recent Tasks with Follow-up Category</h3>";
    $stmt = $db->prepare("SELECT id, title, task_category, assigned_to, created_at FROM tasks WHERE task_category LIKE '%follow%' OR task_category LIKE '%Follow%' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tasks)) {
        echo "<p>No tasks found with follow-up category</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Task ID</th><th>Title</th><th>Category</th><th>Assigned To</th><th>Created</th></tr>";
        foreach ($tasks as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . htmlspecialchars($t['title']) . "</td>";
            echo "<td>" . htmlspecialchars($t['task_category']) . "</td>";
            echo "<td>{$t['assigned_to']}</td>";
            echo "<td>{$t['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<form method="POST">
    <button type="submit" name="create_test" value="1">Create Test Task with Follow-up</button>
</form>

<p>
    <a href="/ergon/tasks/create">Create Real Task</a> | 
    <a href="/ergon/followups">View Follow-ups</a> | 
    <a href="/ergon/debug_followups.php">Debug Follow-ups</a>
</p>