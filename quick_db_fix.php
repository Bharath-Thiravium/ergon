<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    // Add missing columns quickly
    $columns = [
        'remaining_sla_time' => "ALTER TABLE daily_tasks ADD COLUMN remaining_sla_time INT DEFAULT 0",
        'total_pause_duration' => "ALTER TABLE daily_tasks ADD COLUMN total_pause_duration INT DEFAULT 0",
        'overdue_start_time' => "ALTER TABLE daily_tasks ADD COLUMN overdue_start_time TIMESTAMP NULL",
        'time_used' => "ALTER TABLE daily_tasks ADD COLUMN time_used INT DEFAULT 0"
    ];
    
    foreach ($columns as $column => $sql) {
        try {
            $result = $db->query("SHOW COLUMNS FROM daily_tasks LIKE '{$column}'");
            if (!$result->fetch()) {
                $db->exec($sql);
                echo "Added column: {$column}\n";
            }
        } catch (Exception $e) {
            echo "Column {$column}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Database fix completed\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>