<?php
/**
 * Daily Task Rollover Cron Job
 * Run this script daily at midnight to automatically roll over uncompleted tasks
 * 
 * Cron schedule: 0 0 * * * (daily at midnight)
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['manual'])) {
    http_response_code(403);
    die('Access denied. This script should only be run via CLI or with manual parameter.');
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

try {
    echo "Starting daily task rollover with audit traceability...\n";
    echo "Execution Context: DailyPlanner → UnifiedWorkflowController\n";
    echo "Tables Involved: tasks, daily_tasks, daily_task_history, time_logs, daily_planner_audit\n";
    
    $startTime = microtime(true);
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    echo "Processing rollover from {$yesterday} to {$today}...\n";
    
    $rolledOverCount = DailyPlanner::runDailyRollover();
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅ Daily rollover completed successfully!\n";
    echo "📊 Tasks rolled over: {$rolledOverCount}\n";
    echo "⏱️ Execution time: {$executionTime}ms\n";
    echo "🕐 Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "🔍 Audit Trail: Logged in daily_planner_audit table\n";
    echo "📋 Instruction: FetchAndRolloverDailyTasks - COMPLETED\n";
    
    // Enhanced log entry with audit information
    $logEntry = date('Y-m-d H:i:s') . " - Rollover completed: {$rolledOverCount} tasks from {$yesterday} to {$today}, {$executionTime}ms [AUDIT_ENABLED]\n";
    file_put_contents(__DIR__ . '/rollover.log', $logEntry, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    $errorMsg = "❌ Daily rollover failed: " . $e->getMessage();
    echo $errorMsg . "\n";
    echo "📋 Instruction: FetchAndRolloverDailyTasks - FAILED\n";
    error_log($errorMsg);
    
    // Enhanced error log with context
    $logEntry = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . " [AUDIT_ENABLED]\n";
    file_put_contents(__DIR__ . '/rollover.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    exit(1);
}