<?php
/**
 * Manual Task Rollover API Endpoint
 * For testing and manual triggering of task rollover
 */

header('Content-Type: application/json');
session_start();

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Only allow admin/owner roles for manual rollover
if (($_SESSION['role'] ?? 'user') !== 'owner' && ($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

try {
    $targetDate = $_GET['date'] ?? null;
    
    $planner = new DailyPlanner();
    $rolledOverCount = $planner->rolloverUncompletedTasks($targetDate);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully rolled over {$rolledOverCount} tasks",
        'count' => $rolledOverCount,
        'date' => $targetDate ?: date('Y-m-d', strtotime('-1 day'))
    ]);
    
} catch (Exception $e) {
    error_log('Manual rollover API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Rollover failed: ' . $e->getMessage()
    ]);
}