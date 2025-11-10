<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Debug Follow-up Assignment Issue</h2>";
    echo "<p>Current user: {$_SESSION['user_id']} (" . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Unknown') . ")</p>";
    
    // Check recent tasks with follow-up category
    echo "<h3>Recent Tasks with Follow-up Category:</h3>";
    $stmt = $db->prepare("SELECT id, title, task_category, assigned_to, assigned_by, created_at FROM tasks WHERE task_category LIKE '%follow%' OR task_category LIKE '%Follow%' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tasks)) {
        echo "<p>No tasks with follow-up category found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Task ID</th><th>Title</th><th>Category</th><th>Assigned To</th><th>Assigned By</th><th>Created</th></tr>";
        foreach ($tasks as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>" . htmlspecialchars($t['title']) . "</td>";
            echo "<td>" . htmlspecialchars($t['task_category']) . "</td>";
            echo "<td>{$t['assigned_to']}</td>";
            echo "<td>{$t['assigned_by']}</td>";
            echo "<td>{$t['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check follow-ups for each task
    echo "<h3>Follow-ups Created from Tasks:</h3>";
    foreach ($tasks as $task) {
        echo "<h4>Task ID {$task['id']} - Follow-ups:</h4>";
        
        // Check if follow-up exists for this task's assigned user
        $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? AND title LIKE ? ORDER BY created_at DESC");
        $stmt->execute([$task['assigned_to'], '%' . $task['title'] . '%']);
        $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($followups)) {
            echo "<p style='color: red;'>❌ No follow-up found for user {$task['assigned_to']}</p>";
            
            // Try to create follow-up manually
            echo "<form method='POST' style='margin: 10px 0;'>";
            echo "<input type='hidden' name='task_id' value='{$task['id']}'>";
            echo "<input type='hidden' name='assigned_to' value='{$task['assigned_to']}'>";
            echo "<input type='hidden' name='title' value='{$task['title']}'>";
            echo "<button type='submit' name='create_followup' value='1'>Create Follow-up for this Task</button>";
            echo "</form>";
        } else {
            echo "<p style='color: green;'>✅ Follow-up exists:</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Company</th><th>Date</th><th>Status</th></tr>";
            foreach ($followups as $f) {
                echo "<tr>";
                echo "<td>{$f['id']}</td>";
                echo "<td>{$f['user_id']}</td>";
                echo "<td>" . htmlspecialchars($f['title']) . "</td>";
                echo "<td>" . htmlspecialchars($f['company_name'] ?? '') . "</td>";
                echo "<td>{$f['follow_up_date']}</td>";
                echo "<td>{$f['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Check all follow-ups by user
    echo "<h3>All Follow-ups by User:</h3>";
    $stmt = $db->prepare("SELECT user_id, COUNT(*) as count FROM followups GROUP BY user_id ORDER BY count DESC");
    $stmt->execute();
    $userCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userCounts as $count) {
        $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$count['user_id']]);
        $userName = $stmt->fetchColumn() ?: 'Unknown';
        
        echo "<p>User {$count['user_id']} ({$userName}): {$count['count']} follow-ups</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Handle manual follow-up creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_followup'])) {
    try {
        $taskId = $_POST['task_id'];
        $assignedTo = $_POST['assigned_to'];
        $title = $_POST['title'];
        
        $stmt = $db->prepare("
            INSERT INTO followups (
                user_id, title, description, company_name, contact_person, 
                contact_phone, project_name, follow_up_date, reminder_time, 
                original_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $assignedTo,
            'Follow-up: ' . $title,
            'Manually created follow-up for task ID: ' . $taskId,
            'Test Company',
            'Test Contact',
            '123-456-7890',
            'Test Project',
            date('Y-m-d', strtotime('+1 day')),
            '10:00:00',
            date('Y-m-d', strtotime('+1 day'))
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Follow-up created successfully!</p>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create follow-up</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating follow-up: " . $e->getMessage() . "</p>";
    }
}
?>

<p><a href="/ergon/tasks">View Tasks</a> | <a href="/ergon/followups">View Follow-ups</a></p>