<?php // ✅ REBUILT: Standardized API structure with improved error handling and data validation.
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

// Parse input: JSON or form data
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

// Get action and task_id
$action = $_GET['action'] ?? $input['action'] ?? null;
$task_id = $input['task_id'] ?? null;

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

// ✅ REBUILT: Validate task_id for all actions that require it.
if (in_array($action, ['start', 'pause', 'resume', 'update-progress', 'postpone', 'activate-postponed']) && !$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing task_id']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    switch ($action) {
        case 'start':
            if ($planner->startTask($task_id, $userId)) {
                echo json_encode([
                    'success' => true, // ✅ REBUILT: Consistent API response format.
                    'status' => 'running',
                    'label' => 'Break'
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'start failed']);
            }
            break;
            
        case 'pause':
            if ($planner->pauseTask($task_id, $userId)) {
                echo json_encode([
                    'success' => true,
                    'status' => 'on_break',
                    'label' => 'Resume'
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'pause failed']);
            }
            break;
            
        case 'resume':
            if ($planner->resumeTask($task_id, $userId)) {
                echo json_encode([
                    'success' => true,
                    'status' => 'running',
                    'label' => 'Break'
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'resume failed']);
            }
            break;
            
        case 'sla-dashboard':
            $date = $_GET['date'] ?? date('Y-m-d');
            $requestedUserId = $_GET['user_id'] ?? $userId;
            $stats = $planner->getDailyStats($requestedUserId, $date);
            echo json_encode([
                'success' => true,
                'sla_total_seconds' => (int) ($stats['total_sla_seconds'] ?? 0), // ✅ REBUILT: Provides real data.
                'active_seconds' => (int) ($stats['total_active_seconds'] ?? 0),
                'pause_seconds' => (int) ($stats['total_pause_seconds'] ?? 0),
                'total_tasks' => (int) ($stats['total_tasks'] ?? 0),
                'completed_tasks' => (int) ($stats['completed_tasks'] ?? 0),
                'in_progress_tasks' => (int) ($stats['in_progress_tasks'] ?? 0),
                'postponed_tasks' => (int) ($stats['postponed_tasks'] ?? 0)
            ]);
            break;
            
        case 'timer':
            $taskId = $_GET['task_id'] ?? null;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['error' => 'missing task_id for timer']);
                exit;
            }
            
            // Ensure planner instance is available
            if (!isset($planner)) {
                $planner = new DailyPlanner();
            }

            // Simple query with error handling
            $stmt = $db->prepare("
                SELECT 
                    dt.status, dt.start_time, dt.sla_end_time, 
                    COALESCE(dt.active_seconds, 0) as active_seconds, 
                    COALESCE(dt.pause_duration, 0) as pause_duration, 
                    dt.pause_start_time,
                    COALESCE(t.sla_hours, 0.25) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.original_task_id = t.id
                WHERE dt.id = ? AND dt.user_id = ?
            ");
            $stmt->execute([$taskId, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['error' => 'task not found']);
                exit;
            }

            $now = time();
            $remaining_seconds = 0;
            $current_pause_duration = 0;
            $is_overdue = false;

            if ($task['status'] === 'in_progress') {
                if ($task['sla_end_time']) {
                    $sla_end_timestamp = strtotime($task['sla_end_time']);
                    $remaining_seconds = $sla_end_timestamp - $now;
                    
                    if ($remaining_seconds <= 0) {
                        $is_overdue = true;
                        $remaining_seconds = 0;
                        
                        // Start overdue timer if not already started
                        if (!$task['overdue_start_time']) {
                            $planner->startOverdueTimer($taskId);
                        }
                    }
                } else {
                    $remaining_seconds = ($task['sla_hours'] * 3600);
                }
            } elseif ($task['status'] === 'on_break') {
                if ($task['pause_start_time']) {
                    $pause_start_timestamp = strtotime($task['pause_start_time']);
                    if ($pause_start_timestamp > 0) {
                        $current_pause_duration = $now - $pause_start_timestamp;
                    }
                }
                // Calculate remaining from SLA end time
                $remaining_seconds = ($task['sla_end_time']) 
                    ? max(0, strtotime($task['sla_end_time']) - $now) 
                    : ($task['sla_hours'] * 3600);
            } elseif ($task['status'] === 'not_started') {
                $remaining_seconds = ($task['sla_hours'] * 3600);
            }

            $response = [
                'success' => true,
                'active_seconds' => max(0, (int) $task['active_seconds']),
                'remaining_seconds' => max(0, (int) $remaining_seconds),
                'status' => $task['status'],
                'sla_end_time' => $task['sla_end_time'],
                'pause_duration' => max(0, (int) $task['pause_duration']),
                'pause_start_time' => $task['pause_start_time'],
                'current_pause_duration' => max(0, (int) $current_pause_duration),
                'is_overdue' => $is_overdue
            ];

            // ✅ REBUILT: Sanitizes all string outputs to prevent XSS.
            array_walk_recursive($response, function (&$value) {
                if (is_string($value)) $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            });

            echo json_encode($response);
            break;
            
        case 'update-progress':
            $progress = $input['progress'] ?? null;
            $status = $input['status'] ?? null;
            $reason = $input['reason'] ?? '';

            if ($progress === null || $status === null) {
                http_response_code(400);
                echo json_encode(['error' => 'missing progress or status']);
                exit;
            }

            if ($planner->updateTaskProgress($task_id, $userId, (int)$progress, $status, $reason)) {
                echo json_encode(['success' => true, 'message' => 'Progress updated', 'progress' => (int)$progress, 'status' => $status]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'update-progress failed']);
            }
            break;

        case 'postpone':
            $new_date = $input['new_date'] ?? null;
            if (!$new_date) {
                http_response_code(400);
                echo json_encode(['error' => 'missing new_date']);
                exit;
            }
            try {
                if ($planner->postponeTask($task_id, $userId, $new_date)) {
                    echo json_encode(['success' => true, 'message' => 'Task postponed successfully']);
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'activate-postponed':
            // ✅ REBUILT: Activates a postponed task by changing its status.
            if ($planner->updateTaskProgress($task_id, $userId, 0, 'not_started', 'Activated from postponed state')) {
                 echo json_encode(['success' => true, 'message' => 'Task activated']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'activate-postponed failed']);
            }
            break;

        case 'task-history':
            $task_id = $_GET['task_id'] ?? $task_id; // Allow GET for history
            $history = $planner->getTaskHistory($task_id, $userId);
            echo json_encode(['success' => true, 'history' => $history]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'unknown action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}