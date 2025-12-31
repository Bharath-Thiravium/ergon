<?php
/**
 * Daily Task Rollover Cron Job
 * 
 * This script should be run daily at midnight to automatically
 * roll over incomplete tasks to the next date until completion or postponed.
 * 
 * Cron schedule: 0 0 * * * (daily at midnight)
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting daily task rollover...\n";
    
    // Run the daily rollover
    $rolledCount = DailyPlanner::runDailyRollover();
    
    echo "[" . date('Y-m-d H:i:s') . "] Daily rollover completed: {$rolledCount} tasks rolled over\n";
    
    // Log success
    error_log("Daily task rollover completed successfully: {$rolledCount} tasks");
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    error_log("Daily task rollover failed: " . $e->getMessage());
    exit(1);
}
