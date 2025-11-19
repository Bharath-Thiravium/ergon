<?php
/**
 * Database Fix Script for Daily Tasks SLA Issues
 * Run this script to fix the database structure and resolve Start button issues
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // 1. Check current table structure
    echo "\n=== Checking daily_tasks table structure ===\n";
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = array_column($columns, 'Field');
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n";
    
    // 2. Add missing columns
    echo "\n=== Adding missing columns ===\n";
    $requiredColumns = [
        'sla_end_time' => 'TIMESTAMP NULL',
        'total_pause_duration' => 'INT DEFAULT 0'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            try {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN {$column} {$definition}");
                echo "✓ Added column: {$column}\n";
            } catch (Exception $e) {
                echo "✗ Failed to add column {$column}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column {$column} already exists\n";
        }
    }
    
    // 3. Ensure SLA history table exists
    echo "\n=== Creating SLA history table ===\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS sla_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                daily_task_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                timestamp TIMESTAMP NOT NULL,
                duration_seconds INT DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_daily_task_id (daily_task_id)
            )
        ");
        echo "✓ SLA history table created/verified\n";
    } catch (Exception $e) {
        echo "✗ Failed to create SLA history table: " . $e->getMessage() . "\n";
    }
    
    // 4. Fix status column if needed
    echo "\n=== Fixing status column ===\n";
    try {
        $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN status VARCHAR(50) DEFAULT 'not_started'");
        echo "✓ Status column updated to VARCHAR(50)\n";
    } catch (Exception $e) {
        echo "✗ Status column update failed (may already be correct): " . $e->getMessage() . "\n";
    }
    
    // 5. Normalize existing status values
    echo "\n=== Normalizing status values ===\n";
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
        $result = $stmt->execute([$newStatus, $oldStatus]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Updated {$stmt->rowCount()} records from '{$oldStatus}' to '{$newStatus}'\n";
        }
    }
    
    // Set any NULL or empty status to default
    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'not_started' WHERE status IS NULL OR status = ''");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "✓ Fixed {$stmt->rowCount()} records with NULL/empty status\n";
    }
    
    // 6. Test the fix
    echo "\n=== Testing the fix ===\n";
    $stmt = $db->prepare("SELECT COUNT(*) as total, status FROM daily_tasks GROUP BY status");
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current status distribution:\n";
    foreach ($statusCounts as $row) {
        echo "  - {$row['status']}: {$row['total']} tasks\n";
    }
    
    // 7. Verify table structure after changes
    echo "\n=== Final table structure ===\n";
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks");
    $stmt->execute();
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalColumns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
             ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
    }
    
    echo "\n=== Database fix completed successfully! ===\n";
    echo "The Start button should now work properly.\n";
    echo "Please test by:\n";
    echo "1. Going to Daily Planner\n";
    echo "2. Clicking Start on a task\n";
    echo "3. Refreshing the page to verify the status persists\n";
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>