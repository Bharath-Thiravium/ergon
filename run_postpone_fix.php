<?php
// Run postpone columns fix
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Running postpone columns fix...\n";
    
    // Add missing postpone tracking column
    try {
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN postponed_to_date DATE NULL");
        echo "✓ Added postponed_to_date column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ postponed_to_date column already exists\n";
        } else {
            echo "✗ Error adding postponed_to_date column: " . $e->getMessage() . "\n";
        }
    }
    
    // Ensure history tables exist
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
    echo "✓ daily_task_history table ready\n";
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS sla_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            daily_task_id INT NOT NULL,
            action VARCHAR(20) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            duration_seconds INT DEFAULT 0,
            notes TEXT,
            INDEX idx_daily_task_id (daily_task_id)
        )
    ");
    echo "✓ sla_history table ready\n";
    
    echo "\n✅ Postpone fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>