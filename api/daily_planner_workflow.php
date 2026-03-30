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

// Parse input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$action = $_GET['action'] ?? $input['action'] ?? null;
$task_id = $input['task_id'] ?? null;

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

if (in_array($action, ['start', 'pause', 'resume', 'update-progress', 'postpone']) && !$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing task_id']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $db = Database::connect();
    
    switch ($action) {
        case 'start':
            try {
                // Get task with current progress and SLA info
                $stmt = $db->prepare("
                    SELECT dt.id, dt.status, dt.completed_percentage, dt.active_seconds, dt.pause_duration,
                           COALESCE(t.sla_hours, 0.25) as sla_hours
                    FROM daily_tasks dt
                    LEFT JOIN tasks t ON dt.original_task_id = t.id
                    WHERE dt.id = ? AND dt.user_id = ?
                ");
                $stmt->execute([$task_id, $userId]);
                $task = $stmt->fetch();
                
                if (!$task) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Task not found']);
                    exit;
                }
                
                if (!in_array($task['status'], ['not_started', 'assigned'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Task cannot be started from current status: ' . $task['status']]);
                    exit;
                }
                
                $now = date('Y-m-d H:i:s');
                $nowISO = date('c');
                $slaHours = (float)$task['sla_hours'];
                $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $slaHours . ' hours'));
                
                // Start task with proper SLA tracking - reset active_seconds for fresh start
                $stmt = $db->prepare("
                    UPDATE daily_tasks 
                    SET status = 'in_progress', 
                        start_time = ?, 
                        sla_end_time = ?,
                        resume_time = ?,
                        pause_start_time = NULL,
                        active_seconds = 0,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                $result = $stmt->execute([$now, $slaEndTime, $now, $task_id, $userId]);
                

                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'status' => 'in_progress',
                        'label' => 'Break',
                        'message' => 'Task started successfully',
                        'progress' => (int)($task['completed_percentage'] ?? 0),
                        'start_time' => date('c', strtotime($now)),
                        'resume_time' => date('c', strtotime($now)),
                        'active_seconds' => (int)($task['active_seconds'] ?? 0),
                        'total_pause_duration' => (int)($task['pause_duration'] ?? 0),
                        'current_timestamp' => time(),
                        'server_time' => $nowISO
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to start task']);
                }
                
            } catch (Exception $e) {
                error_log('Start task error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Start task error: ' . $e->getMessage()]);
            }
            break;
            
        case 'pause':
            try {
                $stmt = $db->prepare("
                    SELECT id, status, start_time, resume_time, active_seconds, pause_duration FROM daily_tasks 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$task_id, $userId]);
                $task = $stmt->fetch();
                
                if (!$task) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Task not found']);
                    exit;
                }
                
                if ($task['status'] !== 'in_progress') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Task is not in progress. Current status: ' . $task['status']]);
                    exit;
                }
                

                
                $now = date('Y-m-d H:i:s');
                
                // Calculate and store session time when pausing
                $sessionTime = 0;
                $referenceTime = $task['resume_time'] ?: $task['start_time'];
                if ($referenceTime) {
                    $sessionTime = max(0, time() - strtotime($referenceTime));
                }
                
                $newActiveSeconds = (int)$task['active_seconds'] + $sessionTime;
                
                $stmt = $db->prepare("
                    UPDATE daily_tasks 
                    SET status = 'on_break', 
                        pause_start_time = ?,
                        active_seconds = ?,
                        resume_time = NULL,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                $result = $stmt->execute([$now, $newActiveSeconds, $task_id, $userId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'status' => 'on_break',
                        'label' => 'Resume',
                        'pause_start_time' => date('c', strtotime($now)),
                        'resume_time' => null,
                        'active_seconds' => $newActiveSeconds,
                        'pause_duration' => (int)$task['pause_duration'],
                        'current_timestamp' => time()
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to pause task']);
                }
                
            } catch (Exception $e) {
                error_log('Pause task error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Pause task error: ' . $e->getMessage()]);
            }
            break;
            
        case 'resume':
            try {
                $stmt = $db->prepare("
                    SELECT id, status, pause_start_time, pause_duration, active_seconds FROM daily_tasks 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$task_id, $userId]);
                $task = $stmt->fetch();
                
                if (!$task) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Task not found']);
                    exit;
                }
                
                if ($task['status'] !== 'on_break') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Task is not on break. Current status: ' . $task['status']]);
                    exit;
                }
                

                
                $now = date('Y-m-d H:i:s');
                
                // Calculate and store pause session time when resuming
                $pauseSessionTime = 0;
                if ($task['pause_start_time']) {
                    $pauseSessionTime = max(0, time() - strtotime($task['pause_start_time']));
                }
                
                $newPauseDuration = (int)$task['pause_duration'] + $pauseSessionTime;
                
                $stmt = $db->prepare("
                    UPDATE daily_tasks 
                    SET status = 'in_progress', 
                        resume_time = ?,
                        pause_start_time = NULL,
                        pause_duration = ?,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                $result = $stmt->execute([$now, $newPauseDuration, $task_id, $userId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'status' => 'in_progress',
                        'label' => 'Break',
                        'resume_time' => date('c', strtotime($now)),
                        'pause_start_time' => null,
                        'active_seconds' => (int)$task['active_seconds'],
                        'pause_duration' => $newPauseDuration,
                        'current_timestamp' => time()
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to resume task']);
                }
                
            } catch (Exception $e) {
                error_log('Resume task error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Resume task error: ' . $e->getMessage()]);
            }
            break;
            
        case 'update-progress':
            try {
                $progress = $input['progress'] ?? null;
                $status = $input['status'] ?? null;
                
                if ($progress === null || $status === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'missing progress or status']);
                    exit;
                }
                
                $progress = (int)$progress;
                
                // Get the original task ID for syncing
                $stmt = $db->prepare("SELECT original_task_id, task_id FROM daily_tasks WHERE id = ? AND user_id = ?");
                $stmt->execute([$task_id, $userId]);
                $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$dailyTask) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Daily task not found']);
                    exit;
                }
                
                $db->beginTransaction();
                
                // Update daily_tasks table
                if ($progress >= 100 || $status === 'completed') {
                    $status = 'completed';
                    $progress = 100;
                    $completionTime = date('Y-m-d H:i:s');
                    
                    $stmt = $db->prepare("
                        UPDATE daily_tasks 
                        SET status = 'completed', 
                            completed_percentage = 100,
                            completion_time = ?,
                            updated_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $result = $stmt->execute([$completionTime, $task_id, $userId]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE daily_tasks 
                        SET status = ?, 
                            completed_percentage = ?,
                            updated_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $result = $stmt->execute([$status, $progress, $task_id, $userId]);
                }
                
                if ($result) {
                    // Sync with main tasks table if linked
                    $originalTaskId = $dailyTask['original_task_id'] ?: $dailyTask['task_id'];
                    if ($originalTaskId) {
                        $stmt = $db->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$status, $progress, $originalTaskId]);
                    }
                    
                    $db->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Task synced with planner',
                        'progress' => $progress,
                        'status' => $status
                    ]);
                } else {
                    $db->rollback();
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to sync daily task']);
                }
                
            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollback();
                }
                error_log('Update progress error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Update progress error: ' . $e->getMessage()]);
            }
            break;
            
        case 'postpone':
            try {
                $new_date = $input['new_date'] ?? null;
                $reason = $input['reason'] ?? 'Postponed via daily planner';
                
                if (!$new_date) {
                    http_response_code(400);
                    echo json_encode(['error' => 'missing new_date']);
                    exit;
                }
                
                require_once __DIR__ . '/../app/models/DailyPlanner.php';
                $planner = new DailyPlanner();
                
                if ($planner->postponeTask($task_id, $userId, $new_date)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Task postponed to ' . $new_date
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to postpone task']);
                }
                
            } catch (Exception $e) {
                error_log('Postpone task error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Postpone task error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log('Daily planner workflow error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
