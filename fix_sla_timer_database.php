<?php
/**
 * SLA Timer Database Migration
 * Adds required columns for proper SLA countdown, break/resume functionality
 */

require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting SLA Timer Database Migration...\n";
    
    // Check if daily_tasks table exists
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if (!$stmt->fetch()) {
        echo "Creating daily_tasks table...\n";
        $db->exec("
            CREATE TABLE daily_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                original_task_id INT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                scheduled_date DATE NOT NULL,
                planned_start_time TIME NULL,
                planned_duration INT DEFAULT 60,
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(50) DEFAULT 'not_started',
                completed_percentage INT DEFAULT 0,
                start_time TIMESTAMP NULL,
                pause_time TIMESTAMP NULL,
                pause_start_time TIMESTAMP NULL,
                resume_time TIMESTAMP NULL,
                completion_time TIMESTAMP NULL,
                sla_end_time TIMESTAMP NULL,
                active_seconds INT DEFAULT 0,
                pause_duration INT DEFAULT 0,
                postponed_from_date DATE NULL,
                postponed_to_date DATE NULL,
                source_field VARCHAR(50) NULL,
                rollover_source_date DATE NULL,
                rollover_timestamp TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, scheduled_date),
                INDEX idx_task_id (task_id),
                INDEX idx_original_task_id (original_task_id),
                INDEX idx_status (status),
                INDEX idx_rollover_source (rollover_source_date),
                INDEX idx_user_task_date (user_id, original_task_id, scheduled_date)
            )
        ");
        echo "✅ daily_tasks table created\n";
    }
    
    // Add missing columns for SLA timer functionality
    $columns = [
        'remaining_sla_time' => [
            'sql' => "ALTER TABLE daily_tasks ADD COLUMN remaining_sla_time INT DEFAULT 0",
            'description' => 'Stores remaining SLA time when task is paused'
        ],
        'total_pause_duration' => [
            'sql' => "ALTER TABLE daily_tasks ADD COLUMN total_pause_duration INT DEFAULT 0", 
            'description' => 'Cumulative pause duration across multiple break/resume cycles'
        ],
        'overdue_start_time' => [
            'sql' => "ALTER TABLE daily_tasks ADD COLUMN overdue_start_time TIMESTAMP NULL",
            'description' => 'When SLA timer reaches 0 and overdue counting begins'
        ],
        'time_used' => [
            'sql' => "ALTER TABLE daily_tasks ADD COLUMN time_used INT DEFAULT 0",
            'description' => 'Total time actively worked on task (excluding pauses)'
        ]
    ];
    
    foreach ($columns as $column => $config) {
        $result = $db->query("SHOW COLUMNS FROM daily_tasks LIKE '{$column}'");
        
        if (!$result->fetch()) {
            echo "Adding column: {$column}...\n";
            $db->exec($config['sql']);
            echo "✅ {$column} - {$config['description']}\n";
        } else {
            echo "⏭️  Column {$column} already exists\n";
        }
    }
    
    // Add indexes for timer performance
    $indexes = [
        'idx_status_timer' => "ALTER TABLE daily_tasks ADD INDEX idx_status_timer (status, start_time)",
        'idx_sla_end_time' => "ALTER TABLE daily_tasks ADD INDEX idx_sla_end_time (sla_end_time)",
        'idx_pause_start_time' => "ALTER TABLE daily_tasks ADD INDEX idx_pause_start_time (pause_start_time)",
        'idx_remaining_sla' => "ALTER TABLE daily_tasks ADD INDEX idx_remaining_sla (remaining_sla_time)"
    ];
    
    foreach ($indexes as $indexName => $sql) {
        try {
            $db->exec($sql);
            echo "✅ Index {$indexName} added\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⏭️  Index {$indexName} already exists\n";
            } else {
                echo "⚠️  Warning creating index {$indexName}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create daily_task_history table if it doesn't exist
    $stmt = $db->query("SHOW TABLES LIKE 'daily_task_history'");
    if (!$stmt->fetch()) {
        echo "Creating daily_task_history table...\n";
        $db->exec("
            CREATE TABLE daily_task_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                daily_task_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                notes TEXT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_task_id (daily_task_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            )
        ");
        echo "✅ daily_task_history table created\n";
    }
    
    // Update existing tasks to have proper SLA initialization
    echo "Initializing SLA times for existing tasks...\n";
    try {
        $stmt = $db->prepare("
            UPDATE daily_tasks dt
            LEFT JOIN tasks t ON dt.original_task_id = t.id
            SET dt.remaining_sla_time = COALESCE(t.sla_hours, 0.25) * 3600
            WHERE dt.remaining_sla_time = 0 AND dt.status = 'not_started'
        ");
        $stmt->execute();
        $updatedCount = $stmt->rowCount();
        echo "✅ Updated {$updatedCount} tasks with initial SLA times\n";
    } catch (Exception $e) {
        echo "⚠️ SLA initialization skipped: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 SLA Timer Database Migration Completed Successfully!\n\n";
    echo "New Features Added:\n";
    echo "- ⏱️  Proper SLA countdown with pause/resume\n";
    echo "- 🔄 Cumulative pause duration tracking\n";
    echo "- 📊 Overdue timer when SLA expires\n";
    echo "- 💾 Persistent SLA state across break/resume cycles\n";
    echo "- 📝 Complete audit trail for all timer events\n\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>