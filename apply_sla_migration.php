<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Add missing columns
    $columns = [
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS active_seconds INT DEFAULT 0",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS pause_duration INT DEFAULT 0", 
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS sla_end_time TIMESTAMP NULL",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS pause_start_time TIMESTAMP NULL",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS resume_time TIMESTAMP NULL",
        "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS sla_hours DECIMAL(4,2) DEFAULT 0.25"
    ];
    
    foreach ($columns as $sql) {
        try {
            $db->exec($sql);
            echo "✓ " . substr($sql, 0, 50) . "...\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
