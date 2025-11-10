<?php
// Initialize evening update database structure
// Run this once to ensure all required columns exist

require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Initializing Evening Update Database Structure</h2>";
    
    // Ensure all required columns exist in daily_tasks table
    $columns = [
        'completion_notes' => 'ALTER TABLE daily_tasks ADD COLUMN completion_notes TEXT DEFAULT NULL',
        'progress' => 'ALTER TABLE daily_tasks ADD COLUMN progress INT DEFAULT 0',
        'actual_hours' => 'ALTER TABLE daily_tasks ADD COLUMN actual_hours DECIMAL(4,2) DEFAULT 0.00'
    ];
    
    foreach ($columns as $columnName => $sql) {
        try {
            $db->exec($sql);
            echo "<p style='color: green;'>✓ Added $columnName column to daily_tasks</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>- $columnName column already exists in daily_tasks</p>";
        }
    }
    
    // Update status enum to include all needed values
    try {
        $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN status ENUM('planned','pending','in_progress','completed','cancelled','blocked') DEFAULT 'planned'");
        echo "<p style='color: green;'>✓ Updated status enum values in daily_tasks</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>- Status enum already up to date in daily_tasks</p>";
    }
    
    // Ensure all required columns exist in daily_workflow_status table
    $workflowColumns = [
        'total_completed_tasks' => 'ALTER TABLE daily_workflow_status ADD COLUMN total_completed_tasks INT DEFAULT 0',
        'total_actual_hours' => 'ALTER TABLE daily_workflow_status ADD COLUMN total_actual_hours DECIMAL(6,2) DEFAULT 0.00',
        'productivity_score' => 'ALTER TABLE daily_workflow_status ADD COLUMN productivity_score DECIMAL(5,2) DEFAULT 0.00'
    ];
    
    foreach ($workflowColumns as $columnName => $sql) {
        try {
            $db->exec($sql);
            echo "<p style='color: green;'>✓ Added $columnName column to daily_workflow_status</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>- $columnName column already exists in daily_workflow_status</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✅ Evening update database structure initialized successfully!</h3>";
    echo "<p>You can now use the evening update module at: <a href='/ergon/daily-workflow/evening-update'>/ergon/daily-workflow/evening-update</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>