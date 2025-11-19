<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Fix Daily Tasks Columns</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

try {
    $db = Database::connect();
    
    // Check existing columns
    $stmt = $db->query("DESCRIBE daily_tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    echo "<p>Existing columns: " . implode(', ', $existingColumns) . "</p>";
    
    // Add missing columns
    $requiredColumns = [
        'start_time' => 'TIMESTAMP NULL',
        'pause_time' => 'TIMESTAMP NULL', 
        'resume_time' => 'TIMESTAMP NULL',
        'completion_time' => 'TIMESTAMP NULL',
        'active_seconds' => 'INT DEFAULT 0',
        'completed_percentage' => 'INT DEFAULT 0',
        'postponed_from_date' => 'DATE NULL',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            try {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN $column $definition");
                echo "<p class='success'>✅ Added column: $column</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Failed to add $column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>Column $column already exists</p>";
        }
    }
    
    echo "<p class='success'>✅ Database structure updated!</p>";
    echo "<p><a href='test_task_actions.php'>Test Task Actions</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>