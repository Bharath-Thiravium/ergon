<?php
/**
 * SLA Dashboard API Endpoint
 * Provides real-time SLA timing data for the daily planner
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    require_once __DIR__ . '/../app/config/database.php';
    $db = Database::connect();
    
    $userId = $_SESSION['user_id'];
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }
    
    // Get all tasks for the date with SLA information
    $stmt = $db->prepare("
        SELECT 
            dt.id, dt.status, dt.start_time, dt.pause_start_time, 
            dt.active_seconds, dt.pause_duration, dt.completed_percentage,
            COALESCE(t.sla_hours, 0.25) as sla_hours,
            dt.sla_end_time, dt.resume_time
        FROM daily_tasks dt
        LEFT JOIN tasks t ON dt.original_task_id = t.id
        WHERE dt.user_id = ? AND dt.scheduled_date = ?
    ");
    $stmt->execute([$userId, $date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate SLA metrics
    $totalSlaTime = 0;
    $totalTimeUsed = 0;
    $totalRemainingTime = 0;
    $totalPauseTime = 0;
    $currentTime = time();
    
    foreach ($tasks as $task) {
        $slaSeconds = (float)$task['sla_hours'] * 3600;
        $activeSeconds = (int)$task['active_seconds'];
        $pauseSeconds = (int)$task['pause_duration'];
        
        $totalSlaTime += $slaSeconds;
        
        // Calculate current session time for active tasks
        $currentSessionTime = 0;
        if ($task['status'] === 'in_progress' && $task['start_time']) {
            $referenceTime = $task['resume_time'] ?: $task['start_time'];
            $startTimestamp = strtotime($referenceTime);
            if ($startTimestamp > 946684800) { // Valid timestamp after year 2000
                $sessionTime = $currentTime - $startTimestamp;
                if ($sessionTime > 0 && $sessionTime < 86400) { // Sanity check: less than 24 hours
                    $currentSessionTime = $sessionTime;
                }
            }
        }
        
        // Calculate current pause time for paused tasks
        $currentPauseTime = 0;
        if ($task['status'] === 'on_break' && $task['pause_start_time']) {
            $pauseStartTimestamp = strtotime($task['pause_start_time']);
            if ($pauseStartTimestamp > 946684800) { // Valid timestamp
                $pauseTime = $currentTime - $pauseStartTimestamp;
                if ($pauseTime > 0 && $pauseTime < 86400) { // Sanity check
                    $currentPauseTime = $pauseTime;
                }
            }
        }
        
        $totalActiveTime = $activeSeconds + $currentSessionTime;
        $totalTaskPauseTime = $pauseSeconds + $currentPauseTime;
        
        $totalTimeUsed += $totalActiveTime;
        $totalPauseTime += $totalTaskPauseTime;
        $totalRemainingTime += max(0, $slaSeconds - $totalActiveTime);
    }
    
    // Format times as HH:MM:SS
    function formatTime($seconds) {
        if ($seconds < 0) $seconds = 0;
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
    
    $slaData = [
        'total_sla_time' => formatTime($totalSlaTime),
        'total_time_used' => formatTime($totalTimeUsed),
        'total_remaining_time' => formatTime($totalRemainingTime),
        'total_pause_time' => formatTime($totalPauseTime),
        'total_tasks' => count($tasks),
        'completed_tasks' => count(array_filter($tasks, fn($t) => $t['status'] === 'completed')),
        'in_progress_tasks' => count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')),
        'paused_tasks' => count(array_filter($tasks, fn($t) => $t['status'] === 'on_break')),
        'raw_data' => [
            'total_sla_seconds' => $totalSlaTime,
            'total_used_seconds' => $totalTimeUsed,
            'total_remaining_seconds' => $totalRemainingTime,
            'total_pause_seconds' => $totalPauseTime
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'sla_data' => $slaData,
        'timestamp' => date('Y-m-d H:i:s'),
        'date' => $date
    ]);
    
} catch (Exception $e) {
    error_log('SLA Dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch SLA data: ' . $e->getMessage()
    ]);
}
?>
