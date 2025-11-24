<?php
/**
 * Daily Planner Workflow API - Refactored for Security and Maintainability
 * 
 * BUSINESS CHANGE: Default SLA hours changed from 1.0 to 0.25 hours (15 minutes)
 * Justification: Improved task granularity and better time management for short tasks
 * Impact: All new tasks will have 15-minute default SLA unless explicitly set
 */

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

// Configuration constants for maintainability
define('DEFAULT_SLA_HOURS', 0.25); // Changed from 1.0 to 0.25 for better granularity
define('DAILY_PLANNER_BASE_URL', '/ergon/workflow/daily-planner/'); // Configurable URL

// Helper function to safely parse JSON input
function getJsonInput() {
    $rawInput = @file_get_contents('php://input');
    if ($rawInput === false || strlen($rawInput) === 0) {
        return $_POST ?? [];
    }
    $decoded = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $_POST ?? [];
    }
    return is_array($decoded) ? $decoded : [];
}

// Store input for reuse
$requestInput = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestInput = getJsonInput();
}

// CSRF token validation for POST requests (except timer calls)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action !== 'timer') {
        $token = $requestInput['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
    }
}

// Rate limiting - different limits for different actions
$action = $_GET['action'] ?? '';
if ($action === 'timer') {
    // More lenient rate limiting for timer calls
    if (!isset($_SESSION['timer_calls'])) {
        $_SESSION['timer_calls'] = [];
    }
    $now = time();
    $_SESSION['timer_calls'] = array_filter($_SESSION['timer_calls'], function($time) use ($now) {
        return $now - $time < 60;
    });
    if (count($_SESSION['timer_calls']) >= 200) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Timer rate limit exceeded']);
        exit;
    }
    $_SESSION['timer_calls'][] = $now;
} else {
    // Standard rate limiting for other calls
    if (!isset($_SESSION['api_calls'])) {
        $_SESSION['api_calls'] = [];
    }
    $now = time();
    $_SESSION['api_calls'] = array_filter($_SESSION['api_calls'], function($time) use ($now) {
        return $now - $time < 60;
    });
    if (count($_SESSION['api_calls']) >= 50) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
        exit;
    }
    $_SESSION['api_calls'][] = $now;
}

// Sanitize and validate action parameter
$allowedActions = ['sla-dashboard', 'timer', 'start', 'pause', 'resume', 'update-progress', 'postpone', 'auto-rollover'];
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
            // Use prepared statement with parameter binding for security
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(COALESCE(t.sla_hours, ?) * 3600), 0) as sla_total_seconds,
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
            // Execute with proper parameter binding including default SLA
            if (!$stmt->execute([DEFAULT_SLA_HOURS, $userId, $date])) {
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
            
            // Use parameterized query with default SLA constant
            $stmt = $db->prepare("
                SELECT dt.*, COALESCE(t.sla_hours, ?) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.id = ? AND dt.user_id = ?
            ");
            // Execute with default SLA parameter
            if (!$stmt->execute([DEFAULT_SLA_HOURS, $taskId, $userId])) {
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
            $taskId = filter_var($requestInput['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            // Wrap in transaction for atomicity
            $db->beginTransaction();
            try {
                if ($planner->startTask($taskId, $userId)) {
                    logTaskHistory($db, $taskId, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', $userId);
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Task started']);
                } else {
                    $db->rollback();
                    throw new Exception('Failed to start task');
                }
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'pause':
            $taskId = filter_var($requestInput['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            // Wrap in transaction for atomicity
            $db->beginTransaction();
            try {
                if ($planner->pauseTask($taskId, $userId)) {
                    logTaskHistory($db, $taskId, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', $userId);
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Task paused', 'pause_start' => time()]);
                } else {
                    $db->rollback();
                    throw new Exception('Failed to pause task');
                }
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'resume':
            $taskId = filter_var($requestInput['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($taskId === false || $taskId === null) {
                throw new Exception('Valid Task ID required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            // Wrap in transaction for atomicity
            $db->beginTransaction();
            try {
                if ($planner->resumeTask($taskId, $userId)) {
                    logTaskHistory($db, $taskId, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', $userId);
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Task resumed']);
                } else {
                    $db->rollback();
                    throw new Exception('Failed to resume task');
                }
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'update-progress':
            $taskId = filter_var($requestInput['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $progress = filter_var($requestInput['progress'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            $status = filter_var($requestInput['status'] ?? 'completed', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $allowedStatuses = ['not_started', 'in_progress', 'completed', 'on_break', 'postponed'];
            
            if ($taskId === false || $taskId === null || $progress === false || !in_array($status, $allowedStatuses)) {
                throw new Exception('Valid Task ID, progress (0-100), and status required');
            }
            validateTaskOwnership($db, $taskId, $userId);
            
            // Wrap multiple operations in transaction for atomicity
            $db->beginTransaction();
            try {
                if ($planner->updateTaskProgress($taskId, $userId, $progress, $status)) {
                    logTaskHistory($db, $taskId, 'progress_updated', '', $progress . '%', 'Progress updated to ' . $progress . '% via Daily Planner', $userId);
                    
                    if ($progress >= 100) {
                        logTaskHistory($db, $taskId, 'status_changed', 'in_progress', 'completed', 'Task completed via Daily Planner', $userId);
                    }
                    
                    $db->commit();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Progress updated',
                        'progress' => (int)$progress,
                        'status' => htmlspecialchars($status, ENT_QUOTES, 'UTF-8')
                    ]);
                } else {
                    $db->rollback();
                    throw new Exception('Failed to update progress');
                }
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'postpone':
            $taskId = filter_var($requestInput['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $newDate = filter_var($requestInput['new_date'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $reason = filter_var($requestInput['reason'] ?? 'Postponed via Daily Planner', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
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
            
            // Wrap postpone operation in transaction
            $db->beginTransaction();
            try {
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
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Task postponed']);
                } else {
                    $db->rollback();
                    throw new Exception('Failed to postpone task');
                }
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'auto-rollover':
            // Auto-rollover incomplete tasks to next date
            $targetDate = filter_var($_GET['target_date'] ?? date('Y-m-d'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate) || !strtotime($targetDate)) {
                throw new Exception('Invalid target date format');
            }
            
            $db->beginTransaction();
            try {
                $rolledCount = $planner->autoRolloverToNextDate($userId, $targetDate);
                $db->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => "Rolled over {$rolledCount} incomplete tasks",
                    'rolled_count' => $rolledCount
                ]);
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('Daily planner workflow API error: ' . $e->getMessage() . ' | Action: ' . ($action ?? 'unknown') . ' | User: ' . ($userId ?? 'unknown') . ' | Input: ' . json_encode($requestInput ?? []) . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'action' => $action ?? 'unknown',
            'input' => $requestInput ?? [],
            'error' => $e->getMessage()
        ]
    ]);
}