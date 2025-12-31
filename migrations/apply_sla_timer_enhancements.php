<?php
/**
 * SLA Timer Enhancement Migration
 * Applies database schema changes for proper SLA timer functionality
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';

try {
    $db = Database::connect();
    
    echo "Starting SLA Timer Enhancement Migration...\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/../sql/sla_timer_enhancements.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $db->beginTransaction();
    
    $executedCount = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $db->exec($statement);
                $executedCount++;
                echo "✓ Executed statement " . ($executedCount) . "\n";
            } catch (PDOException $e) {
                // Log but continue for statements that might already exist
                if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    $db->commit();
    
    echo "\n✅ SLA Timer Enhancement Migration completed successfully!\n";
    echo "📊 Executed $executedCount SQL statements\n";
    
    // Verify the migration
    echo "\nVerifying migration...\n";
    
    // Check if required columns exist
    $requiredColumns = [
        'active_seconds', 'pause_duration', 'total_pause_duration', 
        'remaining_sla_time', 'time_used', 'sla_end_time', 
        'pause_start_time', 'resume_time'
    ];
    
    $stmt = $db->query("DESCRIBE daily_tasks");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "✅ All required columns are present\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Check if tables exist
    $requiredTables = ['sla_timer_history', 'daily_task_history'];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    echo "\n🎯 Migration verification completed\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
    
    exit(1);
}
?>
