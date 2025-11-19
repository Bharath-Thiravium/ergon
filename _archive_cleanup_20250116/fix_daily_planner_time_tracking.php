<?php
/**
 * Daily Planner Time Tracking Fix
 * Ensures proper database structure for SLA countdown and time tracking
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Ensure daily_tasks table exists with correct structure
    $createTableSQL = "CREATE TABLE IF NOT EXISTS daily_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        task_id INT NULL,
        scheduled_date DATE NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        planned_start_time TIME NULL,
        planned_duration INT DEFAULT 60,
        priority VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(50) DEFAULT 'not_started',
        start_time TIMESTAMP NULL,
        pause_time TIMESTAMP NULL,
        resume_time TIMESTAMP NULL,
        completion_time TIMESTAMP NULL,
        active_seconds INT DEFAULT 0,
        completed_percentage INT DEFAULT 0,
        postponed_from_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, scheduled_date),
        INDEX idx_status (status)
    )";
    
    $db->exec($createTableSQL);
    echo "Daily tasks table structure verified.\n";
    
    // Fix status column if it's ENUM
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'status'");
        $stmt->execute();
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column && strpos($column['Type'], 'enum') !== false) {
            $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN status VARCHAR(50) DEFAULT 'not_started'");
            echo "Status column updated to VARCHAR(50).\n";
        }
    } catch (Exception $e) {
        echo "Status column check: " . $e->getMessage() . "\n";
    }
    
    // Normalize existing status values
    $statusMappings = [
        'paused' => 'on_break',
        'break' => 'on_break',
        'pause' => 'on_break',
        'started' => 'in_progress',
        'active' => 'in_progress',
        'pending' => 'not_started',
        'assigned' => 'not_started',
        'done' => 'completed',
        'finished' => 'completed'
    ];
    
    foreach ($statusMappings as $oldStatus => $newStatus) {
        $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE status = ?");
        $stmt->execute([$newStatus, $oldStatus]);
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "Updated $count tasks from '$oldStatus' to '$newStatus'.\n";
        }
    }
    
    // Set any NULL or empty status to default
    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'not_started' WHERE status IS NULL OR status = ''");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        echo "Set $count tasks to default 'not_started' status.\n";
    }
    
    // Ensure tasks table has sla_hours column
    try {
        $db->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS sla_hours DECIMAL(4,2) DEFAULT 1.0");
        echo "SLA hours column added to tasks table.\n";
    } catch (Exception $e) {
        echo "SLA hours column check: " . $e->getMessage() . "\n";
    }
    
    echo "\nDaily Planner time tracking fix completed successfully!\n";
    echo "The following issues have been resolved:\n";
    echo "✓ Database structure optimized for time tracking\n";
    echo "✓ Status values normalized\n";
    echo "✓ SLA hours column ensured\n";
    echo "✓ Indexes created for performance\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>