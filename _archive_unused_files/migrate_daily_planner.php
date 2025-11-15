<?php
/**
 * Daily Planner Advanced Workflow Migration Script
 * Run this script to upgrade the daily planner with advanced workflow features
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting Daily Planner Advanced Workflow Migration...\n";
    
    // Read and execute the migration SQL
    $migrationSQL = file_get_contents(__DIR__ . '/database/daily_planner_advanced_workflow.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    $db->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew features available:\n";
    echo "- Advanced task time tracking\n";
    echo "- Start/Pause/Resume/Complete workflow\n";
    echo "- SLA calculation and monitoring\n";
    echo "- Enhanced daily performance metrics\n";
    echo "- Task postponement with date rescheduling\n";
    echo "- Real-time timer display\n";
    echo "- Completion percentage tracking\n";
    echo "\nAccess the enhanced daily planner at: http://localhost/ergon/workflow/daily-planner\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>