<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Fix Follow-up Assignment</h2>";
    
    // Check if there are tasks with follow-up category but no corresponding follow-ups
    $stmt = $db->prepare("
        SELECT t.id, t.title, t.task_category, t.assigned_to, t.assigned_by, t.created_at,
               u.name as assigned_user_name
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE (t.task_category LIKE '%follow%' OR t.task_category LIKE '%Follow%')
        AND NOT EXISTS (
            SELECT 1 FROM followups f 
            WHERE f.user_id = t.assigned_to 
            AND f.title LIKE CONCAT('%', t.title, '%')
        )
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    $tasksWithoutFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tasks with Follow-up Category but No Follow-ups Created:</h3>";
    
    if (empty($tasksWithoutFollowups)) {
        echo "<p style='color: green;'>✅ All follow-up tasks have corresponding follow-ups</p>";
    } else {
        echo "<p style='color: orange;'>Found " . count($tasksWithoutFollowups) . " tasks missing follow-ups:</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Task ID</th><th>Title</th><th>Assigned To</th><th>User Name</th><th>Created</th><th>Action</th></tr>";
        
        foreach ($tasksWithoutFollowups as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['assigned_to']}</td>";
            echo "<td>" . htmlspecialchars($task['assigned_user_name'] ?? 'Unknown') . "</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='task_id' value='{$task['id']}'>";
            echo "<input type='hidden' name='assigned_to' value='{$task['assigned_to']}'>";
            echo "<input type='hidden' name='title' value='" . htmlspecialchars($task['title']) . "'>";
            echo "<button type='submit' name='create_missing_followup' value='1' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Create Follow-up</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<button type='submit' name='create_all_missing' value='1' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>Create All Missing Follow-ups</button>";
        echo "</form>";
    }
    
    // Show current follow-ups by user
    echo "<h3>Current Follow-ups by User:</h3>";
    $stmt = $db->prepare("
        SELECT f.user_id, u.name as user_name, COUNT(*) as followup_count
        FROM followups f
        LEFT JOIN users u ON f.user_id = u.id
        GROUP BY f.user_id, u.name
        ORDER BY followup_count DESC
    ");
    $stmt->execute();
    $followupCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($followupCounts)) {
        echo "<p>No follow-ups found in the system</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>User ID</th><th>User Name</th><th>Follow-up Count</th></tr>";
        foreach ($followupCounts as $count) {
            echo "<tr>";
            echo "<td>{$count['user_id']}</td>";
            echo "<td>" . htmlspecialchars($count['user_name'] ?? 'Unknown') . "</td>";
            echo "<td>{$count['followup_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Handle follow-up creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_missing_followup'])) {
            // Create single follow-up
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
                'Auto-created follow-up for task: ' . $title,
                'Company Name',
                'Contact Person',
                '123-456-7890',
                'Project Name',
                date('Y-m-d', strtotime('+1 day')),
                '10:00:00',
                date('Y-m-d', strtotime('+1 day'))
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Follow-up created for task ID {$taskId}</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create follow-up for task ID {$taskId}</p>";
            }
        }
        
        if (isset($_POST['create_all_missing'])) {
            // Create all missing follow-ups
            $created = 0;
            foreach ($tasksWithoutFollowups as $task) {
                $stmt = $db->prepare("
                    INSERT INTO followups (
                        user_id, title, description, company_name, contact_person, 
                        contact_phone, project_name, follow_up_date, reminder_time, 
                        original_date, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $result = $stmt->execute([
                    $task['assigned_to'],
                    'Follow-up: ' . $task['title'],
                    'Auto-created follow-up for task: ' . $task['title'],
                    'Company Name',
                    'Contact Person',
                    '123-456-7890',
                    'Project Name',
                    date('Y-m-d', strtotime('+1 day')),
                    '10:00:00',
                    date('Y-m-d', strtotime('+1 day'))
                ]);
                
                if ($result) {
                    $created++;
                }
            }
            
            echo "<p style='color: green;'>✅ Created {$created} follow-ups out of " . count($tasksWithoutFollowups) . " missing</p>";
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating follow-up: " . $e->getMessage() . "</p>";
    }
}
?>

<div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border-radius: 5px;">
    <h4>Quick Links:</h4>
    <p>
        <a href="/ergon/tasks" style="color: #007cba; text-decoration: none; margin-right: 15px;">→ View Tasks</a>
        <a href="/ergon/followups" style="color: #007cba; text-decoration: none; margin-right: 15px;">→ View Follow-ups</a>
        <a href="/ergon/debug_followup_assignment.php" style="color: #007cba; text-decoration: none;">→ Debug Assignment</a>
    </p>
</div>