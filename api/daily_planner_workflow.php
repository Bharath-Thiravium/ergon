<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

// CSRF token validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Rate limiting
if (!isset($_SESSION['api_calls'])) {
    $_SESSION['api_calls'] = [];
}
$now = time();
$_SESSION['api_calls'] = array_filter($_SESSION['api_calls'], function($time) use ($now) {
    return $now - $time < 60;
});
if (count($_SESSION['api_calls']) >= 100) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}
$_SESSION['api_calls'][] = $now;

// Sanitize and validate action parameter
$allowedActions = ['sla-dashboard', 'timer', 'start', 'pause', 'resume', 'update-progress', 'postpone'];
$action = filter_var($_GET['action'] ?? $_POST['action'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!in_array($action, $allowedActions)) {
    throw new Exception('Invalid action');
}

$userId = (int)$_SESSION['user_id'];
if ($userId <= 0) {
    throw new Exception('Invalid user session');
}

// Helper function to validate task ownership
function validateTaskOwnership($db, $taskId, $userId) {
    $stmt = $db->prepare("SELECT user_id FROM daily_tasks WHERE id = ? AND user_id = ?");
    if (!$stmt->execute([$taskId, $userId])) {
        throw new Exception('Database query failed');
    }
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Task not found or access denied');
    }
    return true;
}

// Helper function to log task history
function logTaskHistory($db, $taskId, $action, $oldValue, $newValue, $notes, $userId) {
    try {
        $stmt = $db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ? AND user_id = ?");
        if (!$stmt->execute([$taskId, $userId])) {
            return false;
        }
        $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dailyTask && $dailyTask['original_task_id']) {
            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([intval($dailyTask['original_task_id']), htmlspecialchars($action), htmlspecialchars($oldValue), htmlspecialchars($newValue), htmlspecialchars($notes), $userId]);
        }
    } catch (Exception $e) {
        error_log('Task history logging failed: ' . $e->getMessage());
    }
}

// Helper function to safely parse JSON input
function getJsonInput() {
    $rawInput = file_get_contents('php://input');
    if ($rawInput === false || strlen($rawInput) === 0) {
        throw new Exception('No input data received');
    }
    if (strlen($rawInput) > 1048576) {
        throw new Exception('Input data too large');
    }
    $decoded = json_decode($rawInput, true, 10, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new Exception('Invalid JSON structure');
    }
    return $decoded;
}

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    switch ($action) {
        case 'sla-dashboard':
            $date = filter_var($_GET['date'] ?? date('Y-m-d'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Validate date format and range
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
                throw new Exception('Invalid date format');
            }
            
            $dateObj = new DateTime($date);
            $today = new DateTime();
            $minDate = new DateTime('-1 year');
            
            if ($dateObj > $today) {
                throw new Exception('Cannot access future dates');
            }
            if ($dateObj < $minDate) {
                throw new Exception('Date too far in the past');
            }
            
            $stats = $planner->getDailyStats($userId, $date);
            
            // Calculate SLA totals
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(COALESCE(t.sla_hours, 1) * 3600), 0) as sla_total_seconds,
                    COALESCE(SUM(dt.active_seconds), 0) as active_seconds,
                    COALESCE(SUM(dt.pause_duration), 0) as pause_seconds,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN dt.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN dt.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN dt.status = 'postponed' THEN 1 ELSE 0 END) as postponed_tasks
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.user_id = ? AND dt.scheduled_date = ?
            ");
            if (!$stmt->execute([$userId, $date])) {
                throw new Exception('Database query failed');
            }
            $slaData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$slaData) {
                throw new Exception('Failed to fetch SLA data');
            }
            
            $slaTotal = $slaData['sla_total_seconds'] ?? 0;
            $activeSeconds = $slaData['active_seconds'] ?? 0;
            $pauseSeconds = $slaData['pause_seconds'] ?? 0;
            $remainingSeconds = max(0, $slaTotal - $activeSeconds);
            
            // Sanitize output data
            $response = [
                'success' => true,
                'user_specific' => true,
                'current_user_id' => (int)$userId,
                'sla_total_seconds' => (int)$slaTotal,
                'active_seconds' => (int)$activeSeconds,
                'remaining_seconds' => (int)$remainingSeconds,
                'pause_seconds' => (int)$pauseSeconds,
                'total_tasks' => (int)($slaData['total_tasks'] ?? 0),
                'completed_tasks' => (int)($slaData['completed_tasks'] ?? 0),
                'in_progress_tasks' => (int)($slaData['in_progress_tasks'] ?? 0),
                'postponed_tasks' => (int)($slaData['postponed_tasks'] ?? 0),
                'completion_rate' => $slaData['total_tasks'] > 0 ? 
                    round(($slaData['completed_tasks'] / $slaData['total_tasks']) * 100, 1) : 0
            ];
            echo json_encode($response);
            break;
            
        case 'timer':
            $taskId = filter_var($_GET['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            $stmt = $db->prepare("
                SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.id = ? AND dt.user_id = ?
            ");
            if (!$stmt->execute([$taskId, $userId])) {
                throw new Exception('Database query failed');
            }
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            $slaSeconds = $task['sla_hours'] * 3600;
            $activeSeconds = $task['active_seconds'] ?? 0;
            $pauseSeconds = $task['pause_duration'] ?? 0;
            
            // Calculate current active time if in progress
            if ($task['status'] === 'in_progress' && $task['start_time']) {
                $startTime = $task['resume_time'] ?: $task['start_time'];
                $currentActive = time() - strtotime($startTime);
                $activeSeconds += $currentActive;
            }
            
            // Calculate current pause duration if on break
            if ($task['status'] === 'on_break' && $task['pause_start_time']) {
                $currentPause = time() - strtotime($task['pause_start_time']);
                $pauseSeconds += $currentPause;
            }
            
            $remainingSeconds = max(0, $slaSeconds - $activeSeconds);
            $isLate = $activeSeconds > $slaSeconds;
            $lateSeconds = $isLate ? $activeSeconds - $slaSeconds : 0;
            
            // Sanitize output data
            $response = [
                'success' => true,
                'active_seconds' => (int)$activeSeconds,
                'remaining_seconds' => (int)$remainingSeconds,
                'pause_duration' => (int)$pauseSeconds,
                'is_late' => (bool)$isLate,
                'late_seconds' => (int)$lateSeconds,
                'sla_seconds' => (int)$slaSeconds
            ];
            echo json_encode($response);
            break;
            
        case 'start':
            $input = getJsonInput();
            $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            if ($planner->startTask($taskId, $userId)) {
                logTaskHistory($db, $taskId, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', $userId);
                echo json_encode(['success' => true, 'message' => 'Task started']);
            } else {
                throw new Exception('Failed to start task');
            }
            break;
            
        case 'pause':
            $input = getJsonInput();
            $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            if ($planner->pauseTask($taskId, $userId)) {
                logTaskHistory($db, $taskId, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', $userId);
                echo json_encode(['success' => true, 'message' => 'Task paused', 'pause_start' => time()]);
            } else {
                throw new Exception('Failed to pause task');
            }
            break;
            
        case 'resume':
            $input = getJsonInput();
            $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            if ($planner->resumeTask($taskId, $userId)) {
                logTaskHistory($db, $taskId, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', $userId);
                echo json_encode(['success' => true, 'message' => 'Task resumed']);
            } else {
                throw new Exception('Failed to resume task');
            }
            break;
            
        case 'update-progress':
            $input = getJsonInput();
            $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $progress = filter_var($input['progress'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            $status = filter_var($input['status'] ?? 'completed', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $allowedStatuses = ['not_started', 'in_progress', 'completed', 'on_break', 'postponed'];
            
            if ($taskId === false || $taskId === null || $progress === false || !in_array($status, $allowedStatuses)) {
                throw new Exception('Valid Task ID, progress (0-100), and status required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            if ($planner->updateTaskProgress($taskId, $userId, $progress, $status)) {
                logTaskHistory($db, $taskId, 'progress_updated', '', $progress . '%', 'Progress updated to ' . $progress . '% via Daily Planner', $userId);
                
                if ($progress >= 100) {
                    logTaskHistory($db, $taskId, 'status_changed', 'in_progress', 'completed', 'Task completed via Daily Planner', $userId);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Progress updated',
                    'progress' => (int)$progress,
                    'status' => htmlspecialchars($status, ENT_QUOTES, 'UTF-8')
                ]);
            } else {
                throw new Exception('Failed to update progress');
            }
            break;
            
        case 'postpone':
            $input = getJsonInput();
            $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $newDate = filter_var($input['new_date'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $reason = filter_var($input['reason'] ?? 'Postponed via Daily Planner', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if ($taskId === false || $taskId === null || !$newDate || strlen($reason) > 500) {
                throw new Exception('Valid Task ID, new date, and reason (max 500 chars) required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            // Validate date format and range
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate) || !strtotime($newDate)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD');
            }
            
            $newDateObj = new DateTime($newDate);
            $today = new DateTime();
            $maxDate = new DateTime('+1 year');
            
            if ($newDateObj < $today) {
                throw new Exception('Cannot postpone to past dates');
            }
            if ($newDateObj > $maxDate) {
                throw new Exception('Cannot postpone more than 1 year ahead');
            }
            
            if ($planner->postponeTask($taskId, $userId, $newDate)) {
                // Get original task data for history
                $stmt = $db->prepare("SELECT original_task_id, scheduled_date FROM daily_tasks WHERE id = ? AND user_id = ?");
                if (!$stmt->execute([$taskId, $userId])) {
                    throw new Exception('Database query failed');
                }
                $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dailyTask && $dailyTask['original_task_id']) {
                    logTaskHistory($db, $taskId, 'postponed', $dailyTask['scheduled_date'], $newDate, $reason, $userId);
                }
                
                echo json_encode(['success' => true, 'message' => 'Task postponed']);
            } else {
                throw new Exception('Failed to postpone task');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('Daily planner workflow API error: ' . $e->getMessage() . ' | User: ' . $userId . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Sanitize error messages for client
    $safeMessages = [
        'Authentication required', 'Invalid CSRF token', 'Rate limit exceeded',
        'Invalid action', 'Invalid user session', 'Valid Task ID required',
        'Task not found or access denied', 'Invalid date format', 'Cannot access future dates',
        'Date too far in the past', 'Cannot postpone to past dates', 'Cannot postpone more than 1 year ahead',
        'Valid Task ID, progress (0-100), and status required', 'Valid Task ID, new date, and reason (max 500 chars) required',
        'Invalid date format. Use YYYY-MM-DD', 'No input data received', 'Input data too large',
        'Invalid JSON structure', 'Failed to start task', 'Failed to pause task',
        'Failed to resume task', 'Failed to update progress', 'Failed to postpone task'
    ];
    
    $clientMessage = in_array($e->getMessage(), $safeMessages) ? 
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') : 'An error occurred';
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $clientMessage,
        'timestamp' => time()
    ]);
}