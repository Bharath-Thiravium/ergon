<?php
session_start();
require_once __DIR__ . '/app/controllers/AdminController.php';

// Set up session for testing
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Athenas Admin';

echo "=== Admin Data Test ===\n";

try {
    $controller = new AdminController();
    
    // Use reflection to access private methods
    $reflection = new ReflectionClass($controller);
    
    // Test getAdminStats
    $getStatsMethod = $reflection->getMethod('getAdminStats');
    $getStatsMethod->setAccessible(true);
    $stats = $getStatsMethod->invoke($controller);
    
    echo "Stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
    
    // Test getRecentTasks
    $getTasksMethod = $reflection->getMethod('getRecentTasks');
    $getTasksMethod->setAccessible(true);
    $tasks = $getTasksMethod->invoke($controller);
    
    echo "Recent Tasks: " . json_encode($tasks, JSON_PRETTY_PRINT) . "\n";
    
    // Test getPendingApprovals
    $getApprovalsMethod = $reflection->getMethod('getPendingApprovals');
    $getApprovalsMethod->setAccessible(true);
    $approvals = $getApprovalsMethod->invoke($controller);
    
    echo "Pending Approvals: " . json_encode($approvals, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>