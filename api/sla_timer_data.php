<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';

$userId = (int)$_SESSION['user_id'];
$date   = $_GET['date']    ?? date('Y-m-d');
$taskId = $_GET['task_id'] ?? null;

try {
    $db = Database::connect();

    if ($taskId) {
        $slaData = getTaskSLAData($db, $taskId, $userId);
        echo json_encode(['success' => true, 'task_sla_data' => $slaData]);
    } else {
        $slaData = getAllTasksSLAData($db, $userId, $date);
        echo json_encode(['success' => true, 'sla_data' => $slaData]);
    }

} catch (Exception $e) {
    error_log('SLA Timer Data API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'message' => $e->getMessage()]);
}

function getTaskSLAData($db, $taskId, $userId) {
    $stmt = $db->prepare("
        SELECT
            dt.id, dt.title, dt.completed_percentage, dt.status,
            dt.start_time, dt.start_ts_ms, dt.resume_time,
            dt.pause_start_time, dt.pause_start_ts_ms,
            dt.active_seconds, dt.paused_accum_ms, dt.pause_duration,
            dt.sla_end_time,
            COALESCE(NULLIF(t.sla_hours, 0), NULLIF(dt.sla_hours, 0), 0.25) as sla_hours,
            COALESCE(dt.sla_duration_seconds, 0) as sla_duration_seconds
        FROM daily_tasks dt
        LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
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
            dt.id, dt.title, dt.completed_percentage, dt.status,
            dt.start_time, dt.start_ts_ms, dt.resume_time,
            dt.pause_start_time, dt.pause_start_ts_ms,
            dt.active_seconds, dt.paused_accum_ms, dt.pause_duration,
            dt.sla_end_time,
            COALESCE(NULLIF(t.sla_hours, 0), NULLIF(dt.sla_hours, 0), 0.25) as sla_hours,
            COALESCE(dt.sla_duration_seconds, 0) as sla_duration_seconds
        FROM daily_tasks dt
        LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
        WHERE dt.user_id = ? AND dt.scheduled_date = ?
        ORDER BY dt.id
    ");
    $stmt->execute([$userId, $date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalSlaSeconds       = 0;
    $totalActiveSeconds    = 0;
    $totalPauseSeconds     = 0;
    $totalRemainingSeconds = 0;
    $taskMetrics           = [];

    foreach ($tasks as $task) {
        $metrics = calculateTaskSLAMetrics($task);
        $taskMetrics[$task['id']] = $metrics;

        $totalSlaSeconds       += $metrics['sla_duration'];
        $totalActiveSeconds    += $metrics['current_active_seconds'];
        $totalPauseSeconds     += $metrics['current_pause_seconds'];
        $totalRemainingSeconds += $metrics['remaining_seconds'];
    }

    return [
        'total_sla_time'       => formatTime($totalSlaSeconds),
        'total_time_used'      => formatTime($totalActiveSeconds),
        'total_remaining_time' => formatTime($totalRemainingSeconds),
        'total_pause_time'     => formatTime($totalPauseSeconds),
        'task_metrics'         => $taskMetrics,
        'summary' => [
            'total_tasks'             => count($tasks),
            'total_sla_seconds'       => $totalSlaSeconds,
            'total_active_seconds'    => $totalActiveSeconds,
            'total_pause_seconds'     => $totalPauseSeconds,
            'total_remaining_seconds' => $totalRemainingSeconds
        ]
    ];
}

function calculateTaskSLAMetrics($task) {
    $nowMs   = (int)(microtime(true) * 1000);
    $now     = time();

    // SLA duration: prefer sla_duration_seconds (written at task start), fall back to sla_hours from tasks table
    $slaDuration = (int)($task['sla_duration_seconds'] ?? 0);
    if ($slaDuration <= 0) {
        $slaHours = (float)($task['sla_hours'] ?? 0);
        $slaDuration = $slaHours > 0 ? max(60, (int)round($slaHours * 3600)) : 900; // 900 = 15 min only when truly no SLA set
    }

    // Elapsed active time: prefer paused_accum_ms (ms precision), fall back to active_seconds
    $accumMs = (int)($task['paused_accum_ms'] ?? 0);
    if ($accumMs <= 0 && isset($task['active_seconds'])) {
        $accumMs = (int)$task['active_seconds'] * 1000;
    }
    $storedActiveSeconds = (int)round($accumMs / 1000);
    $storedPauseSeconds  = (int)($task['pause_duration'] ?? 0);

    $currentActiveSeconds = $storedActiveSeconds;
    $currentPauseSeconds  = $storedPauseSeconds;

    if (in_array($task['status'], ['in_progress', 'overdue'], true)) {
        // Use ms-precision start_ts_ms if available
        if (!empty($task['start_ts_ms'])) {
            $refMs = (int)$task['start_ts_ms'];
            // If resumed, use resume_time as reference
            if (!empty($task['resume_time'])) {
                $refMs = (int)(strtotime($task['resume_time']) * 1000);
            }
            $sessionMs = max(0, $nowMs - $refMs);
            $currentActiveSeconds = $storedActiveSeconds + (int)round($sessionMs / 1000);
        } else {
            $referenceTime = $task['resume_time'] ?: $task['start_time'];
            if ($referenceTime) {
                $currentActiveSeconds += max(0, $now - strtotime($referenceTime));
            }
        }
    } elseif ($task['status'] === 'on_break') {
        // Use ms-precision pause_start_ts_ms if available
        if (!empty($task['pause_start_ts_ms'])) {
            $pauseMs = max(0, $nowMs - (int)$task['pause_start_ts_ms']);
            $currentPauseSeconds = $storedPauseSeconds + (int)round($pauseMs / 1000);
        } elseif ($task['pause_start_time']) {
            $currentPauseSeconds += max(0, $now - strtotime($task['pause_start_time']));
        }
    }

    $remainingSeconds = max(0, $slaDuration - $currentActiveSeconds);
    $isOverdue        = $currentActiveSeconds > $slaDuration;
    $overdueSeconds   = $isOverdue ? $currentActiveSeconds - $slaDuration : 0;

    return [
        'task_id'                => $task['id'],
        'title'                  => $task['title'] ?? ('Task #' . $task['id']),
        'status'                 => $task['status'],
        'completed_percentage'   => (int)($task['completed_percentage'] ?? 0),
        'sla_duration'           => $slaDuration,
        'current_active_seconds' => $currentActiveSeconds,
        'current_pause_seconds'  => $currentPauseSeconds,
        'remaining_seconds'      => $remainingSeconds,
        'overdue_seconds'        => $overdueSeconds,
        'is_overdue'             => $isOverdue,
        'formatted' => [
            'sla_time'       => formatTime($slaDuration),
            'active_time'    => formatTime($currentActiveSeconds),
            'pause_time'     => formatTime($currentPauseSeconds),
            'remaining_time' => formatTime($remainingSeconds),
            'overdue_time'   => formatTime($overdueSeconds)
        ]
    ];
}

function formatTime($seconds) {
    $seconds = max(0, (int)$seconds);
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
}
?>
