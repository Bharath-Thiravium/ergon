<?php
// Daily Planner Advanced Workflow API Endpoints
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

header('Content-Type: application/json');
AuthMiddleware::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $planner = new DailyPlanner();
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'start':
                    $taskId = $input['task_id'] ?? null;
                    if (!$taskId) {
                        echo json_encode(['success' => false, 'message' => 'Task ID required']);
                        break;
                    }
                    
                    $db = Database::connect();
                    $now = date('Y-m-d H:i:s');
                    
                    // Get task and SLA info with detailed debugging
                    $stmt = $db->prepare("SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours FROM daily_tasks dt LEFT JOIN tasks t ON dt.task_id = t.id WHERE dt.id = ? AND dt.user_id = ?");
                    $stmt->execute([$taskId, $userId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task) {
                        // Check if task exists for any user
                        $stmt = $db->prepare("SELECT user_id FROM daily_tasks WHERE id = ?");
                        $stmt->execute([$taskId]);
                        $anyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($anyTask) {
                            echo json_encode(['success' => false, 'message' => "Task belongs to user {$anyTask['user_id']}, not $userId"]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Task ID $taskId does not exist in daily_tasks table"]);
                        }
                        break;
                    }
                    
                    if ($task['status'] !== 'not_started') {
                        echo json_encode(['success' => false, 'message' => 'Task already started']);
                        break;
                    }
                    
                    // Calculate SLA end time
                    $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $task['sla_hours'] . ' hours'));
                    
                    // Start the task
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = ? WHERE id = ?");
                    $result = $stmt->execute([$now, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, notes) VALUES (?, 'start', ?, 'Task started')");
                        $stmt->execute([$taskId, $now]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Task started successfully',
                            'task_id' => $taskId,
                            'status' => 'in_progress',
                            'start_time' => $now,
                            'sla_end_time' => $slaEndTime
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to start task']);
                    }
                    break;
                    
                case 'pause':
                    $taskId = $input['task_id'] ?? null;
                    if (!$taskId) {
                        echo json_encode(['success' => false, 'message' => 'Task ID required']);
                        break;
                    }
                    
                    $db = Database::connect();
                    $now = date('Y-m-d H:i:s');
                    
                    // Get current task data
                    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ? AND user_id = ?");
                    $stmt->execute([$taskId, $userId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task || $task['status'] !== 'in_progress') {
                        echo json_encode(['success' => false, 'message' => 'Task not in progress']);
                        break;
                    }
                    
                    // Calculate active time since start/resume
                    $lastActiveTime = $task['resume_time'] ?: $task['start_time'];
                    $activeSeconds = $lastActiveTime ? (strtotime($now) - strtotime($lastActiveTime)) : 0;
                    $newTotalActive = $task['active_seconds'] + $activeSeconds;
                    
                    // Update task to paused state
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'on_break', pause_time = ?, active_seconds = ? WHERE id = ?");
                    $result = $stmt->execute([$now, $newTotalActive, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, duration_seconds, notes) VALUES (?, 'pause', ?, ?, 'Task paused')");
                        $stmt->execute([$taskId, $now, $activeSeconds]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Task paused successfully',
                            'task_id' => $taskId,
                            'status' => 'on_break',
                            'active_seconds' => $newTotalActive
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to pause task']);
                    }
                    break;
                    
                case 'resume':
                    $taskId = $input['task_id'] ?? null;
                    if (!$taskId) {
                        echo json_encode(['success' => false, 'message' => 'Task ID required']);
                        break;
                    }
                    
                    $db = Database::connect();
                    $now = date('Y-m-d H:i:s');
                    
                    // Get current task data
                    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ? AND user_id = ?");
                    $stmt->execute([$taskId, $userId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task || $task['status'] !== 'on_break') {
                        echo json_encode(['success' => false, 'message' => 'Task not paused']);
                        break;
                    }
                    
                    // Calculate pause duration
                    $pauseDuration = $task['pause_time'] ? (strtotime($now) - strtotime($task['pause_time'])) : 0;
                    $newTotalPause = $task['total_pause_duration'] + $pauseDuration;
                    
                    // Resume the task
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', resume_time = ?, total_pause_duration = ? WHERE id = ?");
                    $result = $stmt->execute([$now, $newTotalPause, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, duration_seconds, notes) VALUES (?, 'resume', ?, ?, 'Task resumed')");
                        $stmt->execute([$taskId, $now, $pauseDuration]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Task resumed successfully',
                            'task_id' => $taskId,
                            'status' => 'in_progress',
                            'total_pause_duration' => $newTotalPause
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to resume task']);
                    }
                    break;
                    
                case 'complete':
                    $taskId = $input['task_id'] ?? null;
                    $percentage = $input['percentage'] ?? 100;
                    
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    $result = $planner->completeTask($taskId, $userId, $percentage);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task completed' : 'Failed to complete task']);
                    break;
                    
                case 'update-progress':
                    $taskId = $input['task_id'] ?? null;
                    $progress = intval($input['progress'] ?? 0);
                    $status = $input['status'] ?? 'in_progress';
                    $reason = trim($input['reason'] ?? '');
                    
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    // Validate progress range
                    if ($progress < 0 || $progress > 100) {
                        throw new Exception('Progress must be between 0 and 100');
                    }
                    
                    $result = $planner->updateTaskProgress($taskId, $userId, $progress, $status, $reason);
                    echo json_encode([
                        'success' => $result, 
                        'message' => $result ? 'Progress updated successfully' : 'Failed to update progress',
                        'progress' => $progress,
                        'status' => $status
                    ]);
                    break;
                    
                case 'postpone':
                    $taskId = $input['task_id'] ?? null;
                    $newDate = $input['new_date'] ?? null;
                    
                    if (!$taskId || !$newDate) {
                        throw new Exception('Task ID and new date required');
                    }
                    
                    $result = $planner->postponeTask($taskId, $userId, $newDate);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task postponed' : 'Failed to postpone task']);
                    break;
                    
                case 'quick-add':
                    $title = trim($_POST['title'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $scheduledDate = $_POST['scheduled_date'] ?? date('Y-m-d');
                    $plannedTime = $_POST['planned_time'] ?? null;
                    $duration = $_POST['duration'] ?? 60;
                    $priority = $_POST['priority'] ?? 'medium';
                    
                    if (empty($title)) {
                        throw new Exception('Title is required');
                    }
                    
                    $db = Database::connect();
                    $stmt = $db->prepare("
                        INSERT INTO daily_tasks 
                        (user_id, scheduled_date, title, description, planned_start_time, planned_duration, priority, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
                    ");
                    
                    $result = $stmt->execute([
                        $userId, $scheduledDate, $title, $description, 
                        $plannedTime, $duration, $priority
                    ]);
                    
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task added successfully' : 'Failed to add task']);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'timer':
                    $taskId = $_GET['task_id'] ?? null;
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    $db = Database::connect();
                    $stmt = $db->prepare("
                        SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours
                        FROM daily_tasks dt 
                        LEFT JOIN tasks t ON dt.task_id = t.id
                        WHERE dt.id = ? AND dt.user_id = ?
                    ");
                    $stmt->execute([$taskId, $userId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($task) {
                        $now = time();
                        $slaSeconds = $task['sla_hours'] * 3600;
                        $currentActiveTime = 0;
                        $remainingTime = $slaSeconds;
                        $isLate = false;
                        $lateTime = 0;
                        
                        if ($task['status'] === 'in_progress' && $task['start_time']) {
                            $lastActiveTime = $task['resume_time'] ?: $task['start_time'];
                            $currentActiveTime = $now - strtotime($lastActiveTime);
                        }
                        
                        $totalActiveTime = $task['active_seconds'] + $currentActiveTime;
                        $remainingTime = $slaSeconds - $totalActiveTime;
                        
                        if ($remainingTime < 0) {
                            $isLate = true;
                            $lateTime = abs($remainingTime);
                            $remainingTime = 0;
                        }
                        
                        echo json_encode([
                            'success' => true,
                            'status' => $task['status'],
                            'sla_seconds' => $slaSeconds,
                            'active_seconds' => $totalActiveTime,
                            'remaining_seconds' => $remainingTime,
                            'pause_duration' => $task['total_pause_duration'],
                            'is_late' => $isLate,
                            'late_seconds' => $lateTime,
                            'start_time' => $task['start_time'],
                            'sla_end_time' => $task['sla_end_time']
                        ]);
                    } else {
                        throw new Exception('Task not found');
                    }
                    break;
                    
                case 'stats':
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $stats = $planner->getDailyStats($userId, $date);
                    echo json_encode(['success' => true, 'stats' => $stats]);
                    break;
                    
                case 'task-history':
                    $taskId = $_GET['task_id'] ?? null;
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    $history = $planner->getTaskHistory($taskId, $userId);
                    echo json_encode(['success' => true, 'history' => $history]);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    error_log('Daily Planner Workflow API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>