<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Link Follow-ups to Tasks</h2>";
    
    // Add task_id column if it doesn't exist
    try {
        $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('task_id', $columns)) {
            $db->exec("ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER user_id, ADD INDEX idx_task_id (task_id)");
            echo "<p>✅ Added task_id column to followups table</p>";
        } else {
            echo "<p>✅ task_id column already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error adding task_id column: " . $e->getMessage() . "</p>";
    }
    
    // Find follow-ups that might be linked to tasks
    $stmt = $db->prepare("
        SELECT f.id as followup_id, f.title as followup_title, f.task_id,
               t.id as task_id, t.title as task_title, t.assigned_to
        FROM followups f
        LEFT JOIN tasks t ON (
            f.title LIKE CONCAT('%', t.title, '%') OR 
            t.title LIKE CONCAT('%', SUBSTRING(f.title, LOCATE(':', f.title) + 2), '%')
        )
        WHERE f.task_id IS NULL 
        AND t.id IS NOT NULL
        AND f.user_id = t.assigned_to
        ORDER BY f.created_at DESC
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Potential Follow-up to Task Links:</h3>";
    
    if (empty($matches)) {
        echo "<p>No potential links found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Follow-up ID</th><th>Follow-up Title</th><th>Task ID</th><th>Task Title</th><th>Action</th></tr>";
        
        foreach ($matches as $match) {
            echo "<tr>";
            echo "<td>{$match['followup_id']}</td>";
            echo "<td>" . htmlspecialchars($match['followup_title']) . "</td>";
            echo "<td>{$match['task_id']}</td>";
            echo "<td>" . htmlspecialchars($match['task_title']) . "</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='followup_id' value='{$match['followup_id']}'>";
            echo "<input type='hidden' name='task_id' value='{$match['task_id']}'>";
            echo "<button type='submit' name='link' value='1' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Link</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<button type='submit' name='link_all' value='1' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>Link All Matches</button>";
        echo "</form>";
    }
    
    // Show current linked follow-ups
    echo "<h3>Currently Linked Follow-ups:</h3>";
    $stmt = $db->prepare("
        SELECT f.id, f.title as followup_title, f.status as followup_status,
               t.id as task_id, t.title as task_title, t.status as task_status, t.progress
        FROM followups f
        INNER JOIN tasks t ON f.task_id = t.id
        ORDER BY f.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $linked = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($linked)) {
        echo "<p>No linked follow-ups found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Follow-up</th><th>F Status</th><th>Task</th><th>T Status</th><th>Progress</th></tr>";
        foreach ($linked as $link) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($link['followup_title']) . "</td>";
            echo "<td>{$link['followup_status']}</td>";
            echo "<td>" . htmlspecialchars($link['task_title']) . "</td>";
            echo "<td>{$link['task_status']}</td>";
            echo "<td>{$link['progress']}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Handle linking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['link'])) {
            $followupId = $_POST['followup_id'];
            $taskId = $_POST['task_id'];
            
            $stmt = $db->prepare("UPDATE followups SET task_id = ? WHERE id = ?");
            $result = $stmt->execute([$taskId, $followupId]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Linked follow-up {$followupId} to task {$taskId}</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to link follow-up {$followupId} to task {$taskId}</p>";
            }
        }
        
        if (isset($_POST['link_all'])) {
            $linked = 0;
            foreach ($matches as $match) {
                $stmt = $db->prepare("UPDATE followups SET task_id = ? WHERE id = ?");
                $result = $stmt->execute([$match['task_id'], $match['followup_id']]);
                if ($result) {
                    $linked++;
                }
            }
            echo "<p style='color: green;'>✅ Linked {$linked} follow-ups to their tasks</p>";
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error linking: " . $e->getMessage() . "</p>";
    }
}
?>

<div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border-radius: 5px;">
    <h4>How it works:</h4>
    <p>When a follow-up is marked as completed, the linked task will automatically be:</p>
    <ul>
        <li>Status changed to "completed"</li>
        <li>Progress set to 100%</li>
    </ul>
    <p><strong>Note:</strong> Only follow-ups created from tasks will have this automatic linking.</p>
</div>

<p><a href="/ergon/followups">Back to Follow-ups</a> | <a href="/ergon/tasks">View Tasks</a></p>