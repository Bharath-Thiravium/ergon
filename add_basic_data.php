<?php
/**
 * Add Basic Dummy Data - Safe Version
 * This script only inserts data, no table modifications
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Adding basic dummy data...\n";
    
    // Execute the basic SQL file
    $sqlFile = __DIR__ . '/database/basic_dummy_data.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split and execute statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $successCount++;
        } catch (Exception $e) {
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Basic dummy data added! ($successCount statements executed)\n";
    
    // Verify data
    $taskCount = $db->query("SELECT COUNT(*) FROM tasks WHERE title LIKE '%Email%' OR title LIKE '%Meeting%'")->fetchColumn();
    $plannerCount = $db->query("SELECT COUNT(*) FROM daily_planner WHERE title LIKE '%Email%' OR title LIKE '%Meeting%'")->fetchColumn();
    $updateCount = $db->query("SELECT COUNT(*) FROM evening_updates WHERE title LIKE '%Daily Update%'")->fetchColumn();
    
    echo "\nData added:\n";
    echo "Tasks: $taskCount\n";
    echo "Planner entries: $plannerCount\n";
    echo "Evening updates: $updateCount\n";
    
    echo "\n🎉 Ready to test the unified workflow system!\n";
    echo "Visit: /ergon/workflow/daily-planner\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>