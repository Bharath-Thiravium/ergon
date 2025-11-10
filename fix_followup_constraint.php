<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Fix Follow-up Foreign Key Constraint</h2>";
    
    // Check current constraints
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'followups' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Foreign Key Constraints:</h3>";
    if (empty($constraints)) {
        echo "<p>No foreign key constraints found</p>";
    } else {
        foreach ($constraints as $constraint) {
            echo "<p>Constraint: {$constraint['CONSTRAINT_NAME']} - Column: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</p>";
        }
    }
    
    // Drop the incorrect foreign key constraint
    echo "<h3>Fixing Constraints:</h3>";
    
    try {
        // Drop existing foreign key constraints on task_id
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'followups' 
            AND COLUMN_NAME = 'task_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $taskConstraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($taskConstraints as $constraintName) {
            $db->exec("ALTER TABLE followups DROP FOREIGN KEY `{$constraintName}`");
            echo "<p>✅ Dropped constraint: {$constraintName}</p>";
        }
        
        // Add task_id column if it doesn't exist
        $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('task_id', $columns)) {
            $db->exec("ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER user_id");
            echo "<p>✅ Added task_id column</p>";
        } else {
            echo "<p>✅ task_id column already exists</p>";
        }
        
        // Add index if it doesn't exist
        $indexes = $db->query("SHOW INDEX FROM followups WHERE Column_name = 'task_id'")->fetchAll();
        if (empty($indexes)) {
            $db->exec("ALTER TABLE followups ADD INDEX idx_task_id (task_id)");
            echo "<p>✅ Added index on task_id</p>";
        } else {
            echo "<p>✅ Index on task_id already exists</p>";
        }
        
        echo "<p style='color: green;'>✅ All constraints fixed! Follow-ups can now be linked to tasks without foreign key restrictions.</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error fixing constraints: " . $e->getMessage() . "</p>";
    }
    
    // Test linking capability
    echo "<h3>Test Linking:</h3>";
    
    // Find a task and follow-up to test with
    $stmt = $db->query("SELECT id, title FROM tasks LIMIT 1");
    $testTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT id, title FROM followups WHERE task_id IS NULL LIMIT 1");
    $testFollowup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testTask && $testFollowup) {
        echo "<p>Test task: {$testTask['title']} (ID: {$testTask['id']})</p>";
        echo "<p>Test follow-up: {$testFollowup['title']} (ID: {$testFollowup['id']})</p>";
        
        if (isset($_POST['test_link'])) {
            try {
                $stmt = $db->prepare("UPDATE followups SET task_id = ? WHERE id = ?");
                $result = $stmt->execute([$testTask['id'], $testFollowup['id']]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Test linking successful!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Test linking failed</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Test linking error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='test_link' value='1' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>Test Link</button>";
            echo "</form>";
        }
    } else {
        echo "<p>No tasks or follow-ups available for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<div style="margin: 20px 0; padding: 15px; background: #d4edda; border-radius: 5px;">
    <h4>What was fixed:</h4>
    <ul>
        <li>Removed incorrect foreign key constraint linking task_id to daily_plans table</li>
        <li>Made task_id a simple INT column without foreign key restrictions</li>
        <li>Added proper index for performance</li>
        <li>Now follow-ups can be linked to any task ID without constraint violations</li>
    </ul>
</div>

<p><a href="/ergon/link_followups_to_tasks.php">Link Follow-ups to Tasks</a> | <a href="/ergon/followups">Back to Follow-ups</a></p>