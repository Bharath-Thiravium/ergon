<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Creating missing indexes...\n";
    
    $indexes = [
        "daily_tasks" => [
            "idx_daily_tasks_user_date" => "(user_id, scheduled_date)",
            "idx_daily_tasks_status" => "(status)",
            "idx_daily_tasks_start_time" => "(start_time)"
        ],
        "sla_history" => [
            "idx_sla_history_task" => "(daily_task_id)"
        ],
        "time_logs" => [
            "idx_time_logs_task" => "(daily_task_id)"
        ],
        "tasks" => [
            "idx_tasks_assigned_to" => "(assigned_to)",
            "idx_tasks_status" => "(status)"
        ]
    ];
    
    foreach ($indexes as $table => $tableIndexes) {
        foreach ($tableIndexes as $indexName => $columns) {
            try {
                $db->exec("CREATE INDEX {$indexName} ON {$table} {$columns}");
                echo "✓ Created index {$indexName} on {$table}\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "✓ Index {$indexName} already exists\n";
                } else {
                    echo "✗ Failed to create {$indexName}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\nIndex creation completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>