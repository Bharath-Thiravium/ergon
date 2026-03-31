<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$action  = $_GET['action'] ?? $input['action'] ?? null;
$task_id = $input['task_id'] ?? null;

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

if (in_array($action, ['start', 'pause', 'resume', 'update-progress', 'postpone', 'mark-overdue']) && !$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing task_id']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $db = Database::connect();

    switch ($action) {

        // ── START ─────────────────────────────────────────────────────────────
        case 'start':
            $stmt = $db->prepare("
                SELECT dt.id, dt.task_id, dt.original_task_id, dt.status, dt.completed_percentage,
                       COALESCE(t.sla_hours, dt.sla_hours, 0.25) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
                WHERE dt.id = ? AND dt.user_id = ?
            ");
            $stmt->execute([$task_id, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) { http_response_code(404); echo json_encode(['error' => 'Task not found']); exit; }
            if (!in_array($task['status'], ['not_started', 'assigned'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Task cannot be started. Status: ' . $task['status']]);
                exit;
            }

            $nowMs              = (int)(microtime(true) * 1000);
            $slaDurationSeconds = max(60, (int)round((float)$task['sla_hours'] * 3600));

            // start_ts_ms = now  |  paused_accum_ms = 0  |  status = in_progress
            $stmt = $db->prepare("
                UPDATE daily_tasks
                SET status            = 'in_progress',
                    start_ts_ms       = ?,
                    sla_duration_seconds = ?,
                    paused_accum_ms   = 0,
                    pause_start_ts_ms = NULL,
                    start_time        = NOW(),
                    updated_at        = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$nowMs, $slaDurationSeconds, $task_id, $userId]);

            if ($stmt->rowCount() === 0) { http_response_code(400); echo json_encode(['error' => 'Failed to start task']); exit; }

            $linked = (int)($task['original_task_id'] ?: $task['task_id']);
            if ($linked > 0) {
                $db->prepare("UPDATE tasks SET status='in_progress', updated_at=NOW() WHERE id=?")->execute([$linked]);
            }

            echo json_encode([
                'success'              => true,
                'status'               => 'in_progress',
                'start_ts_ms'          => $nowMs,
                'paused_accum_ms'      => 0,
                'sla_duration_seconds' => $slaDurationSeconds,
            ]);
            break;

        // ── PAUSE ─────────────────────────────────────────────────────────────
        case 'pause':
            $stmt = $db->prepare("
                SELECT id, task_id, original_task_id, status,
                       start_ts_ms, paused_accum_ms
                FROM daily_tasks WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$task_id, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) { http_response_code(404); echo json_encode(['error' => 'Task not found']); exit; }
            if (!in_array($task['status'], ['in_progress', 'overdue'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Task is not running. Status: ' . $task['status']]);
                exit;
            }

            $nowMs       = (int)(microtime(true) * 1000);
            $startTsMs   = (int)($task['start_ts_ms'] ?? 0);
            $sessionMs   = $startTsMs > 0 ? max(0, $nowMs - $startTsMs) : 0;

            // paused_accum_ms += (now - start_ts_ms)
            $newAccumMs  = (int)($task['paused_accum_ms'] ?? 0) + $sessionMs;

            $stmt = $db->prepare("
                UPDATE daily_tasks
                SET status            = 'on_break',
                    pause_start_ts_ms = ?,
                    paused_accum_ms   = ?,
                    start_ts_ms       = NULL,
                    updated_at        = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$nowMs, $newAccumMs, $task_id, $userId]);

            if ($stmt->rowCount() === 0) { http_response_code(400); echo json_encode(['error' => 'Failed to pause task']); exit; }

            $linked = (int)($task['original_task_id'] ?: $task['task_id']);
            if ($linked > 0) {
                $db->prepare("UPDATE tasks SET status='on_break', updated_at=NOW() WHERE id=?")->execute([$linked]);
            }

            echo json_encode([
                'success'          => true,
                'status'           => 'on_break',
                'pause_start_ts_ms'=> $nowMs,
                'paused_accum_ms'  => $newAccumMs,
            ]);
            break;

        // ── RESUME ────────────────────────────────────────────────────────────
        case 'resume':
            $stmt = $db->prepare("
                SELECT dt.id, dt.task_id, dt.original_task_id, dt.status,
                       dt.pause_start_ts_ms, dt.paused_accum_ms,
                       COALESCE(t.sla_hours, dt.sla_hours, 0.25) as sla_hours,
                       dt.sla_duration_seconds
                FROM daily_tasks dt
                LEFT JOIN tasks t ON t.id = COALESCE(dt.original_task_id, dt.task_id)
                WHERE dt.id = ? AND dt.user_id = ?
            ");
            $stmt->execute([$task_id, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) { http_response_code(404); echo json_encode(['error' => 'Task not found']); exit; }
            if ($task['status'] !== 'on_break') {
                http_response_code(400);
                echo json_encode(['error' => 'Task is not paused. Status: ' . $task['status']]);
                exit;
            }

            $nowMs       = (int)(microtime(true) * 1000);
            $pauseStartMs= (int)($task['pause_start_ts_ms'] ?? 0);
            $pausedMs    = $pauseStartMs > 0 ? max(0, $nowMs - $pauseStartMs) : 0;

            // paused_accum_ms += pause_duration  (accumulate the break time — NOT counted as work)
            $newAccumMs  = (int)($task['paused_accum_ms'] ?? 0);
            // start_ts_ms = now  (fresh reference for next working session)

            $slaDurSec   = (int)($task['sla_duration_seconds'] ?? 0);
            if ($slaDurSec <= 0) {
                $slaDurSec = max(60, (int)round((float)$task['sla_hours'] * 3600));
            }
            $elapsedMs   = $newAccumMs;
            $nextStatus  = ($elapsedMs >= $slaDurSec * 1000) ? 'overdue' : 'in_progress';

            $stmt = $db->prepare("
                UPDATE daily_tasks
                SET status            = ?,
                    start_ts_ms       = ?,
                    pause_start_ts_ms = NULL,
                    paused_accum_ms   = ?,
                    updated_at        = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$nextStatus, $nowMs, $newAccumMs, $task_id, $userId]);

            if ($stmt->rowCount() === 0) { http_response_code(400); echo json_encode(['error' => 'Failed to resume task']); exit; }

            $linked = (int)($task['original_task_id'] ?: $task['task_id']);
            if ($linked > 0) {
                $db->prepare("UPDATE tasks SET status=?, updated_at=NOW() WHERE id=?")->execute([$nextStatus, $linked]);
            }

            echo json_encode([
                'success'          => true,
                'status'           => $nextStatus,
                'start_ts_ms'      => $nowMs,
                'paused_accum_ms'  => $newAccumMs,
                'sla_duration_seconds' => $slaDurSec,
            ]);
            break;

        // ── MARK OVERDUE ──────────────────────────────────────────────────────
        case 'mark-overdue':
            $stmt = $db->prepare("SELECT id, task_id, original_task_id, status FROM daily_tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) { http_response_code(404); echo json_encode(['error' => 'Task not found']); exit; }
            if ($task['status'] === 'overdue') { echo json_encode(['success' => true, 'status' => 'overdue']); exit; }
            if ($task['status'] !== 'in_progress') {
                http_response_code(400);
                echo json_encode(['error' => 'Only in_progress tasks can be marked overdue']);
                exit;
            }

            $db->prepare("UPDATE daily_tasks SET status='overdue', updated_at=NOW() WHERE id=? AND user_id=?")->execute([$task_id, $userId]);

            $linked = (int)($task['original_task_id'] ?: $task['task_id']);
            if ($linked > 0) {
                $db->prepare("UPDATE tasks SET status='overdue', updated_at=NOW() WHERE id=?")->execute([$linked]);
            }

            echo json_encode(['success' => true, 'status' => 'overdue']);
            break;

        // ── UPDATE PROGRESS ───────────────────────────────────────────────────
        case 'update-progress':
            $progress = $input['progress'] ?? null;
            $status   = $input['status']   ?? null;

            if ($progress === null || $status === null) {
                http_response_code(400); echo json_encode(['error' => 'missing progress or status']); exit;
            }

            $progress = (int)$progress;
            $stmt = $db->prepare("SELECT original_task_id, task_id FROM daily_tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $userId]);
            $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dailyTask) { http_response_code(404); echo json_encode(['error' => 'Task not found']); exit; }

            $db->beginTransaction();
            if ($progress >= 100 || $status === 'completed') {
                $status = 'completed'; $progress = 100;
                $db->prepare("UPDATE daily_tasks SET status='completed', completed_percentage=100, completion_time=NOW(), updated_at=NOW() WHERE id=? AND user_id=?")->execute([$task_id, $userId]);
            } else {
                $db->prepare("UPDATE daily_tasks SET status=?, completed_percentage=?, updated_at=NOW() WHERE id=? AND user_id=?")->execute([$status, $progress, $task_id, $userId]);
            }

            $origId = $dailyTask['original_task_id'] ?: $dailyTask['task_id'];
            if ($origId) {
                $db->prepare("UPDATE tasks SET status=?, progress=?, updated_at=NOW() WHERE id=?")->execute([$status, $progress, $origId]);
            }
            $db->commit();

            echo json_encode(['success' => true, 'progress' => $progress, 'status' => $status]);
            break;

        // ── POSTPONE ──────────────────────────────────────────────────────────
        case 'postpone':
            $new_date = $input['new_date'] ?? null;
            if (!$new_date) { http_response_code(400); echo json_encode(['error' => 'missing new_date']); exit; }

            require_once __DIR__ . '/../app/models/DailyPlanner.php';
            $planner = new DailyPlanner();
            try {
                if ($planner->postponeTask($task_id, $userId, $new_date)) {
                    echo json_encode(['success' => true, 'message' => 'Task postponed to ' . $new_date]);
                } else {
                    http_response_code(400); echo json_encode(['error' => 'Failed to postpone task']);
                }
            } catch (Exception $e) {
                http_response_code(400); echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action: ' . $action]);
    }

} catch (Exception $e) {
    error_log('Daily planner workflow error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
