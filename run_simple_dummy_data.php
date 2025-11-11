<?php
/**
 * Run Simple Dummy Data Population
 * Execute this to add basic dummy data for testing
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Adding dummy data for unified workflow...\n";
    
    // Read the simple SQL file
    $sqlFile = __DIR__ . '/database/simple_dummy_data.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split and execute SQL statements individually to handle errors
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
        } catch (Exception $e) {
            // Ignore column already exists errors
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✅ Dummy data added successfully!\n";
    
    // Verify the data
    $taskCount = $db->query("SELECT COUNT(*) FROM tasks WHERE title LIKE '%Email Review%'")->fetchColumn();
    $plannerCount = $db->query("SELECT COUNT(*) FROM daily_planner WHERE title LIKE '%Email Review%'")->fetchColumn();
    $updateCount = $db->query("SELECT COUNT(*) FROM evening_updates WHERE title LIKE '%Daily Update%'")->fetchColumn();
    
    echo "\nData verification:\n";
    echo "Sample tasks: $taskCount\n";
    echo "Planner entries: $plannerCount\n";
    echo "Evening updates: $updateCount\n";
    
    echo "\nYou can now test the unified workflow system!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>