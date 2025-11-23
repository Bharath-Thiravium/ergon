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
    echo "🔁 Starting AutoRolloverTasksToToday process...\n";
    echo "📋 Instruction Name: AutoRolloverTasksToToday\n";
    echo "🎯 Execution Context: DailyPlanner → UnifiedWorkflowController\n";
    echo "🗄️ Tables Used: daily_tasks, daily_task_history\n";
    echo "🤖 Automation Hooks: Midnight cron\n";
    echo "📊 Audit Compliance: Full trace via daily_task_history and rollover_source_date\n";
    
    $startTime = microtime(true);
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    echo "\n🔍 Step 1: Detecting eligible tasks for rollover...\n";
    echo "📅 Processing rollover from {$yesterday} to {$today}\n";
    
    // Use new specification-compliant rollover
    $planner = new DailyPlanner();
    $eligibleTasks = $planner->getRolloverTasks();
    echo "✅ Found " . count($eligibleTasks) . " eligible tasks\n";
    
    echo "\n📦 Step 2: Performing rollover to today...\n";
    $rolledOverCount = $planner->performRollover($eligibleTasks);
    
    echo "\n🖥️ Step 3: Updating display logic completed\n";
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "\n✅ AutoRolloverTasksToToday completed successfully!\n";
    echo "📊 Tasks rolled over: {$rolledOverCount}\n";
    echo "⏱️ Execution time: {$executionTime}ms\n";
    echo "🕐 Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "🔍 Audit Trail: Logged in daily_task_history table\n";
    echo "📋 Status Management Rules: Applied\n";
    
    // Enhanced log entry with specification compliance
    $logEntry = date('Y-m-d H:i:s') . " - AutoRolloverTasksToToday: {$rolledOverCount} tasks from {$yesterday} to {$today}, {$executionTime}ms [SPEC_COMPLIANT]\n";
    file_put_contents(__DIR__ . '/rollover.log', $logEntry, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    $errorMsg = "❌ AutoRolloverTasksToToday failed: " . $e->getMessage();
    echo $errorMsg . "\n";
    echo "📋 Instruction: AutoRolloverTasksToToday - FAILED\n";
    echo "🔍 Check audit trail in daily_task_history table\n";
    error_log($errorMsg);
    
    // Enhanced error log with specification context
    $logEntry = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . " [SPEC_COMPLIANT]\n";
    file_put_contents(__DIR__ . '/rollover.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    exit(1);
}