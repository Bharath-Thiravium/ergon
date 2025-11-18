<?php
// Quick SQL fix runner for production
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Running SQL Fixes</h2>";
    
    // Check if columns exist first
    $stmt = $db->query("DESCRIBE daily_tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Current Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    // Add missing columns
    $fixes = [
        "ALTER TABLE daily_tasks ADD COLUMN sla_end_time DATETIME NULL",
        "ALTER TABLE daily_tasks ADD COLUMN active_seconds INT DEFAULT 0", 
        "ALTER TABLE daily_tasks ADD COLUMN total_pause_duration INT DEFAULT 0",
        "ALTER TABLE daily_tasks ADD COLUMN resume_time DATETIME NULL",
        "ALTER TABLE daily_tasks ADD COLUMN pause_time DATETIME NULL"
    ];
    
    echo "<h3>Applying Fixes:</h3>";
    foreach ($fixes as $sql) {
        try {
            $db->exec($sql);
            echo "✅ " . $sql . "<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠️ Column already exists: " . $sql . "<br>";
            } else {
                echo "❌ Error: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<h3>Updated Columns:</h3>";
    $stmt = $db->query("DESCRIBE daily_tasks");
    $newColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($newColumns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>✅ Database fixes applied successfully!</strong></p>";
    echo "<p><a href='workflow/daily-planner'>Test Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>