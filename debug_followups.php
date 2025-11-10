<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Debug: Follow-ups for User ID: " . $_SESSION['user_id'] . "</h2>";
    
    // Check if followups table exists
    $stmt = $db->query("SHOW TABLES LIKE 'followups'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ followups table does not exist</p>";
        exit;
    }
    echo "<p>✅ followups table exists</p>";
    
    // Get all follow-ups for current user
    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total follow-ups found: " . count($followups) . "</p>";
    
    if (empty($followups)) {
        echo "<p>No follow-ups found for this user.</p>";
        
        // Check if there are any follow-ups at all
        $stmt = $db->query("SELECT COUNT(*) FROM followups");
        $total = $stmt->fetchColumn();
        echo "<p>Total follow-ups in system: " . $total . "</p>";
        
        if ($total > 0) {
            $stmt = $db->query("SELECT user_id, COUNT(*) as count FROM followups GROUP BY user_id");
            $userCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Follow-ups by user:</h3>";
            foreach ($userCounts as $count) {
                echo "<p>User ID {$count['user_id']}: {$count['count']} follow-ups</p>";
            }
        }
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
    
    // Check recent tasks with follow-up category
    echo "<h3>Recent tasks with 'follow' in category:</h3>";
    $stmt = $db->prepare("SELECT id, title, task_category, assigned_to, created_at FROM tasks WHERE task_category LIKE '%follow%' OR task_category LIKE '%Follow%' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tasks)) {
        echo "<p>No tasks found with 'follow' in category</p>";
        
        // Show all task categories to help debug
        echo "<h4>All task categories in system:</h4>";
        $stmt = $db->query("SELECT DISTINCT task_category FROM tasks WHERE task_category IS NOT NULL AND task_category != '' ORDER BY task_category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($categories)) {
            echo "<p>No task categories found</p>";
        } else {
            echo "<ul>";
            foreach ($categories as $cat) {
                echo "<li>" . htmlspecialchars($cat) . "</li>";
            }
            echo "</ul>";
        }
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
    
    // Check if followup_history table exists
    echo "<h3>Follow-up History Table Check:</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'followup_history'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ followup_history table does not exist</p>";
    } else {
        echo "<p>✅ followup_history table exists</p>";
        $stmt = $db->query("SELECT COUNT(*) FROM followup_history");
        $historyCount = $stmt->fetchColumn();
        echo "<p>Total history records: $historyCount</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="/ergon/tasks/create">Create Task</a> | <a href="/ergon/followups">View Follow-ups</a></p>