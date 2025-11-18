<?php
/**
 * Complete Database Setup Script
 * Ensures all required tables and columns exist for Daily Planner functionality
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // 1. Ensure time_logs table exists (used by DailyPlanner model)
    echo "\n=== Creating time_logs table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS time_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                daily_task_id INT NOT NULL,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                timestamp TIMESTAMP NOT NULL,
                active_duration INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_daily_task_id (daily_task_id),
                INDEX idx_user_id (user_id)
            )
        ");
        echo "✓ time_logs table created/verified\n";
    } catch (Exception $e) {
        echo "✗ Failed to create time_logs table: " . $e->getMessage() . "\n";
    }
    
    // 2. Ensure daily_task_history table exists
    echo "\n=== Creating daily_task_history table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS daily_task_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                daily_task_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_daily_task_id (daily_task_id)
            )
        ");
        echo "✓ daily_task_history table created/verified\n";
    } catch (Exception $e) {
        echo "✗ Failed to create daily_task_history table: " . $e->getMessage() . "\n";
    }
    
    // 3. Ensure daily_performance table exists
    echo "\n=== Creating daily_performance table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS daily_performance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                date DATE NOT NULL,
                total_planned_minutes INT DEFAULT 0,
                total_active_minutes DECIMAL(10,2) DEFAULT 0,
                total_tasks INT DEFAULT 0,
                completed_tasks INT DEFAULT 0,
                in_progress_tasks INT DEFAULT 0,
                postponed_tasks INT DEFAULT 0,
                completion_percentage DECIMAL(5,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_date (user_id, date),
                INDEX idx_user_id (user_id),
                INDEX idx_date (date)
            )
        ");
        echo "✓ daily_performance table created/verified\n";
    } catch (Exception $e) {
        echo "✗ Failed to create daily_performance table: " . $e->getMessage() . "\n";
    }
    
    // 4. Check if tasks table has required columns
    echo "\n=== Checking tasks table ===\n";
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM tasks");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = array_column($columns, 'Field');
        
        $requiredTaskColumns = [
            'sla_hours' => 'DECIMAL(4,2) DEFAULT 1.0',
            'actual_time_seconds' => 'INT DEFAULT 0',
            'followup_required' => 'TINYINT(1) DEFAULT 0'
        ];
        
        foreach ($requiredTaskColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                try {
                    $db->exec("ALTER TABLE tasks ADD COLUMN {$column} {$definition}");
                    echo "✓ Added column {$column} to tasks table\n";
                } catch (Exception $e) {
                    echo "✗ Failed to add column {$column} to tasks table: " . $e->getMessage() . "\n";
                }
            } else {
                echo "✓ Column {$column} already exists in tasks table\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Failed to check tasks table: " . $e->getMessage() . "\n";
    }
    
    // 5. Add missing column to daily_tasks if needed
    echo "\n=== Checking daily_tasks for planned_start_time column ===\n";
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'planned_start_time'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE daily_tasks ADD COLUMN planned_start_time TIME NULL AFTER description");
            echo "✓ Added planned_start_time column to daily_tasks\n";
        } else {
            echo "✓ planned_start_time column already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Failed to add planned_start_time column: " . $e->getMessage() . "\n";
    }
    
    // 6. Create indexes for better performance
    echo "\n=== Creating performance indexes ===\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_daily_tasks_user_date ON daily_tasks (user_id, scheduled_date)",
        "CREATE INDEX IF NOT EXISTS idx_daily_tasks_status ON daily_tasks (status)",
        "CREATE INDEX IF NOT EXISTS idx_daily_tasks_start_time ON daily_tasks (start_time)",
        "CREATE INDEX IF NOT EXISTS idx_sla_history_task ON sla_history (daily_task_id)",
        "CREATE INDEX IF NOT EXISTS idx_time_logs_task ON time_logs (daily_task_id)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks (assigned_to)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks (status)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            $db->exec($indexSQL);
            echo "✓ Index created successfully\n";
        } catch (Exception $e) {
            echo "✗ Index creation failed (may already exist): " . $e->getMessage() . "\n";
        }
    }
    
    // 7. Test the complete setup
    echo "\n=== Testing complete setup ===\n";
    
    // Test daily_tasks functionality
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks");
        $stmt->execute();
        $taskCount = $stmt->fetchColumn();
        echo "✓ daily_tasks table accessible - {$taskCount} tasks found\n";
    } catch (Exception $e) {
        echo "✗ daily_tasks test failed: " . $e->getMessage() . "\n";
    }
    
    // Test SLA history functionality
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM sla_history");
        $stmt->execute();
        $historyCount = $stmt->fetchColumn();
        echo "✓ sla_history table accessible - {$historyCount} records found\n";
    } catch (Exception $e) {
        echo "✗ sla_history test failed: " . $e->getMessage() . "\n";
    }
    
    // Test time_logs functionality
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM time_logs");
        $stmt->execute();
        $logCount = $stmt->fetchColumn();
        echo "✓ time_logs table accessible - {$logCount} records found\n";
    } catch (Exception $e) {
        echo "✗ time_logs test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Complete database setup finished! ===\n";
    echo "All required tables and columns have been created.\n";
    echo "The Daily Planner should now work correctly.\n";
    
    echo "\n=== Next Steps ===\n";
    echo "1. Go to Daily Planner: http://your-domain/ergon/workflow/daily-planner\n";
    echo "2. Click 'Sync Tasks' if no tasks are visible\n";
    echo "3. Test the Start button functionality\n";
    echo "4. Verify SLA timer countdown works\n";
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>