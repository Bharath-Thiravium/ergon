<?php
/**
 * Populate Unified Workflow System with Dummy Data
 * Run this script to add sample data for testing
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting dummy data population...\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/populate_unified_workflow_data.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $successCount++;
        } catch (Exception $e) {
            $errorCount++;
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Dummy data population completed!\n";
    echo "Successful statements: $successCount\n";
    echo "Warnings/Errors: $errorCount\n";
    
    // Verify data was inserted
    $stmt = $db->query("SELECT COUNT(*) as task_count FROM tasks WHERE id >= 101");
    $taskCount = $stmt->fetch(PDO::FETCH_ASSOC)['task_count'];
    
    $stmt = $db->query("SELECT COUNT(*) as planner_count FROM daily_planner WHERE id >= 201");
    $plannerCount = $stmt->fetch(PDO::FETCH_ASSOC)['planner_count'];
    
    $stmt = $db->query("SELECT COUNT(*) as update_count FROM evening_updates WHERE id >= 301");
    $updateCount = $stmt->fetch(PDO::FETCH_ASSOC)['update_count'];
    
    echo "\nData verification:\n";
    echo "Tasks inserted: $taskCount\n";
    echo "Daily planner entries: $plannerCount\n";
    echo "Evening updates: $updateCount\n";
    
    if ($taskCount > 0 && $plannerCount > 0 && $updateCount > 0) {
        echo "\n✅ All dummy data successfully populated!\n";
        echo "You can now test the unified workflow system with sample data.\n";
    } else {
        echo "\n⚠️  Some data may not have been inserted properly.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>