<?php
// Test postpone fix deployment
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    echo "✓ Database connection successful\n";
    
    // Check if postponed_to_date column exists
    $stmt = $db->query("SHOW COLUMNS FROM daily_tasks LIKE 'postponed_to_date'");
    if ($stmt->rowCount() > 0) {
        echo "✓ postponed_to_date column exists\n";
    } else {
        echo "✗ postponed_to_date column missing\n";
    }
    
    // Test DailyPlanner stats
    $planner = new DailyPlanner();
    $stats = $planner->getDailyStats(1, date('Y-m-d'));
    echo "✓ getDailyStats working: " . json_encode($stats) . "\n";
    
    // Check history tables
    $stmt = $db->query("SHOW TABLES LIKE 'daily_task_history'");
    echo $stmt->rowCount() > 0 ? "✓ daily_task_history table exists\n" : "✗ daily_task_history table missing\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'sla_history'");
    echo $stmt->rowCount() > 0 ? "✓ sla_history table exists\n" : "✗ sla_history table missing\n";
    
    echo "\n✅ Deployment verification complete\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>