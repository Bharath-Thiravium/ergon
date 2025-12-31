<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check and add columns one by one
    $columns = [
        'active_seconds' => "ALTER TABLE daily_tasks ADD COLUMN active_seconds INT DEFAULT 0",
        'pause_duration' => "ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0", 
        'sla_end_time' => "ALTER TABLE daily_tasks ADD COLUMN sla_end_time TIMESTAMP NULL",
        'pause_start_time' => "ALTER TABLE daily_tasks ADD COLUMN pause_start_time TIMESTAMP NULL",
        'resume_time' => "ALTER TABLE daily_tasks ADD COLUMN resume_time TIMESTAMP NULL"
    ];
    
    // Check existing columns
    $stmt = $db->query("DESCRIBE daily_tasks");
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    foreach ($columns as $column => $sql) {
        if (!in_array($column, $existingColumns)) {
            try {
                $db->exec($sql);
                echo "✓ Added column: $column\n";
            } catch (Exception $e) {
                echo "✗ Error adding $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Column $column already exists\n";
        }
    }
    
    // Check tasks table for sla_hours
    try {
        $stmt = $db->query("DESCRIBE tasks");
        $taskColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        if (!in_array('sla_hours', $taskColumns)) {
            $db->exec("ALTER TABLE tasks ADD COLUMN sla_hours DECIMAL(4,2) DEFAULT 0.25");
            echo "✓ Added sla_hours to tasks table\n";
        } else {
            echo "- Column sla_hours already exists in tasks table\n";
        }
    } catch (Exception $e) {
        echo "✗ Error with tasks table: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ SLA Migration completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
