<?php
ob_start();
ini_set('session.cookie_domain', '');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
error_reporting(0);
ini_set('display_errors', 0);
session_start();
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    require_once __DIR__ . '/../app/config/database.php';
    ob_clean();
    $db = Database::connect();

    $userId = (int)$_SESSION['user_id'];
    $date   = $_GET['date'] ?? date('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }

    $stmt = $db->prepare("
        SELECT dt.status,
               dt.start_ts_ms,
               dt.paused_accum_ms,
               COALESCE(dt.sla_duration_seconds,
                        COALESCE(t.sla_hours, dt.sla_hours, 0.25) * 3600) AS sla_duration_seconds
        FROM daily_tasks dt
        LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
        WHERE dt.user_id = ? AND dt.scheduled_date = ?
    ");
    $stmt->execute([$userId, $date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nowMs            = (int)(microtime(true) * 1000);
    $totalSlaDurMs    = 0;
    $totalWorkingMs   = 0;
    $totalRemainingMs = 0;

    foreach ($tasks as $task) {
        $slaDurSec  = max(60, (int)round((float)$task['sla_duration_seconds']));
        $slaDurMs   = $slaDurSec * 1000;
        $accumMs    = (int)($task['paused_accum_ms'] ?? 0);
        $startTsMs  = (int)($task['start_ts_ms']    ?? 0);

        // Spec: working_time = paused_accum_ms + (now - start_ts_ms)  if in_progress
        //                    = paused_accum_ms                          otherwise
        if (in_array($task['status'], ['in_progress', 'overdue'], true) && $startTsMs > 0) {
            $workingMs = $accumMs + max(0, $nowMs - $startTsMs);
        } else {
            $workingMs = $accumMs;
        }

        $totalSlaDurMs    += $slaDurMs;
        $totalWorkingMs   += $workingMs;
        $totalRemainingMs += max(0, $slaDurMs - $workingMs);
    }

    function fmtMs(int $ms): string {
        $sec = max(0, (int)($ms / 1000));
        return sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
    }

    echo json_encode([
        'success'  => true,
        'sla_data' => [
            'total_sla_time'       => fmtMs($totalSlaDurMs),
            'total_time_used'      => fmtMs($totalWorkingMs),
            'total_remaining_time' => fmtMs($totalRemainingMs),
            'total_pause_time'     => '00:00:00',
            'total_tasks'          => count($tasks),
            'completed_tasks'      => count(array_filter($tasks, fn($t) => $t['status'] === 'completed')),
            'in_progress_tasks'    => count(array_filter($tasks, fn($t) => in_array($t['status'], ['in_progress', 'overdue'], true))),
            'postponed_tasks'      => count(array_filter($tasks, fn($t) => $t['status'] === 'postponed')),
        ],
        'date'     => $date,
    ]);

} catch (Exception $e) {
    error_log('SLA Dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
