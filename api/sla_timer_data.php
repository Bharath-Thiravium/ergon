<?php
/**
 * SLA Timer Data API
 * Provides accurate SLA timer data for the daily planner
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';

$userId = (int)$_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$taskId = $_GET['task_id'] ?? null;

try {
    $db = Database::connect();
    
    if ($taskId) {
        // Get specific task SLA data
        $slaData = getTaskSLAData($db, $taskId, $userId);
        echo json_encode([
            'success' => true,
            'task_sla_data' => $slaData
        ]);
    } else {
        // Get all tasks SLA data for the date
        $slaData = getAllTasksSLAData($db, $userId, $date);
        echo json_encode([
            'success' => true,
            'sla_data' => $slaData
        ]);
    }
    
} catch (Exception $e) {
    error_log('SLA Timer Data API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

function getTaskSLAData($db, $taskId, $userId) {
    $stmt = $db->prepare("
        SELECT 
            dt.id, dt.status, dt.start_time, dt.resume_time, dt.pause_start_time,
            dt.active_seconds, dt.pause_duration, dt.sla_end_time,
            COALESCE(t.sla_hours, 0.25) as sla_hours
        FROM daily_tasks dt
        LEFT JOIN tasks t ON dt.original_task_id = t.id
        WHERE dt.id = ? AND dt.user_id = ?
    ");
    $stmt->execute([$taskId, $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        throw new Exception('Task not found');
    }
    
    return calculateTaskSLAMetrics($task);
}

function getAllTasksSLAData($db, $userId, $date) {
    $stmt = $db->prepare("
        SELECT 
            dt.id, dt.status, dt.start_time, dt.resume_time, dt.pause_start_time,
            dt.active_seconds, dt.pause_duration, dt.sla_end_time,
            COALESCE(t.sla_hours, 0.25) as sla_hours
        FROM daily_tasks dt
        LEFT JOIN tasks t ON dt.original_task_id = t.id
        WHERE dt.user_id = ? AND dt.scheduled_date = ?
        ORDER BY dt.id
    ");
    $stmt->execute([$userId, $date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalSlaSeconds = 0;
    $totalActiveSeconds = 0;
    $totalPauseSeconds = 0;
    $totalRemainingSeconds = 0;
    
    $taskMetrics = [];
    
    foreach ($tasks as $task) {
        $metrics = calculateTaskSLAMetrics($task);
        $taskMetrics[$task['id']] = $metrics;
        
        $totalSlaSeconds += $metrics['sla_duration'];
        $totalActiveSeconds += $metrics['current_active_seconds'];
        $totalPauseSeconds += $metrics['current_pause_seconds'];
        $totalRemainingSeconds += $metrics['remaining_seconds'];
    }
    
    return [
        'total_sla_time' => formatTime($totalSlaSeconds),
        'total_time_used' => formatTime($totalActiveSeconds),
        'total_remaining_time' => formatTime($totalRemainingSeconds),
        'total_pause_time' => formatTime($totalPauseSeconds),
        'task_metrics' => $taskMetrics,
        'summary' => [
            'total_tasks' => count($tasks),
            'total_sla_seconds' => $totalSlaSeconds,
            'total_active_seconds' => $totalActiveSeconds,
            'total_pause_seconds' => $totalPauseSeconds,
            'total_remaining_seconds' => $totalRemainingSeconds
        ]
    ];
}

function calculateTaskSLAMetrics($task) {
    $now = time();
    $slaDuration = (int)($task['sla_hours'] * 3600);
    $storedActiveSeconds = (int)($task['active_seconds'] ?? 0);
    $storedPauseSeconds = (int)($task['pause_duration'] ?? 0);
    
    $currentActiveSeconds = $storedActiveSeconds;
    $currentPauseSeconds = $storedPauseSeconds;
    
    // Calculate current session time
    if ($task['status'] === 'in_progress') {
        $referenceTime = $task['resume_time'] ?: $task['start_time'];
        if ($referenceTime) {
            $sessionTime = max(0, $now - strtotime($referenceTime));
            $currentActiveSeconds += $sessionTime;
        }
    } elseif ($task['status'] === 'on_break' && $task['pause_start_time']) {
        $sessionPauseTime = max(0, $now - strtotime($task['pause_start_time']));
        $currentPauseSeconds += $sessionPauseTime;
    }
    
    // Calculate SLA metrics
    $remainingSeconds = max(0, $slaDuration - $currentActiveSeconds);
    $isOverdue = $currentActiveSeconds > $slaDuration;
    $overdueSeconds = $isOverdue ? $currentActiveSeconds - $slaDuration : 0;
    
    return [
        'task_id' => $task['id'],
        'status' => $task['status'],
        'sla_duration' => $slaDuration,
        'current_active_seconds' => $currentActiveSeconds,
        'current_pause_seconds' => $currentPauseSeconds,
        'remaining_seconds' => $remainingSeconds,
        'overdue_seconds' => $overdueSeconds,
        'is_overdue' => $isOverdue,
        'formatted' => [
            'sla_time' => formatTime($slaDuration),
            'active_time' => formatTime($currentActiveSeconds),
            'pause_time' => formatTime($currentPauseSeconds),
            'remaining_time' => formatTime($remainingSeconds),
            'overdue_time' => formatTime($overdueSeconds)
        ]
    ];
}

function formatTime($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
?>
