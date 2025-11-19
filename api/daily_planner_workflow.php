<?php
// Daily Planner Advanced Workflow API Endpoints
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

header('Content-Type: application/json');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session or set default
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
    $userId = 1;
}

// Debug log
error_log("Daily Planner API - User ID: $userId, Action: " . ($_GET['action'] ?? 'none'));

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $planner = new DailyPlanner();
    // Use the userId set above
    // $userId is already set from session handling above
    
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
                    
                    // Get task and SLA info
                    $stmt = $db->prepare("SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours FROM daily_tasks dt LEFT JOIN tasks t ON dt.task_id = t.id WHERE dt.id = ?");
                    $stmt->execute([$taskId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task) {
                        echo json_encode(['success' => false, 'message' => 'Task not found']);
                        break;
                    }
                    
                    if (!in_array($task['status'], ['not_started', 'assigned'])) {
                        echo json_encode(['success' => false, 'message' => 'Task already started or completed']);
                        break;
                    }
                    
                    // Calculate SLA end time
                    $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $task['sla_hours'] . ' hours'));
                    
                    // Start the task
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = ?, sla_end_time = ?, resume_time = NULL, pause_time = NULL WHERE id = ?");
                    $result = $stmt->execute([$now, $slaEndTime, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        try {
                            $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, notes) VALUES (?, 'start', ?, 'Task started')");
                            $stmt->execute([$taskId, $now]);
                        } catch (Exception $e) {
                            error_log('SLA history log error: ' . $e->getMessage());
                        }
                        
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
                    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task || $task['status'] !== 'in_progress') {
                        echo json_encode(['success' => false, 'message' => 'Task not in progress']);
                        break;
                    }
                    
                    // Calculate active time since start/resume
                    $lastActiveTime = $task['resume_time'] ?: $task['start_time'];
                    $activeSeconds = $lastActiveTime ? (strtotime($now) - strtotime($lastActiveTime)) : 0;
                    $newTotalActive = intval($task['active_seconds'] ?? 0) + $activeSeconds;
                    
                    // Update task to paused state
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'on_break', pause_time = ?, active_seconds = ?, resume_time = NULL WHERE id = ?");
                    $result = $stmt->execute([$now, $newTotalActive, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        try {
                            $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, duration_seconds, notes) VALUES (?, 'pause', ?, ?, 'Task paused')");
                            $stmt->execute([$taskId, $now, $activeSeconds]);
                        } catch (Exception $e) {
                            error_log('SLA history log error: ' . $e->getMessage());
                        }
                        
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
                    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$task || $task['status'] !== 'on_break') {
                        echo json_encode(['success' => false, 'message' => 'Task not paused']);
                        break;
                    }
                    
                    // Calculate pause duration
                    $pauseDuration = $task['pause_time'] ? (strtotime($now) - strtotime($task['pause_time'])) : 0;
                    $newTotalPause = intval($task['pause_duration'] ?? 0) + $pauseDuration;
                    
                    // Resume the task
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', resume_time = ?, pause_duration = ?, pause_time = NULL WHERE id = ?");
                    $result = $stmt->execute([$now, $newTotalPause, $taskId]);
                    
                    if ($result) {
                        // Log SLA history
                        try {
                            $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, duration_seconds, notes) VALUES (?, 'resume', ?, ?, 'Task resumed')");
                            $stmt->execute([$taskId, $now, $pauseDuration]);
                        } catch (Exception $e) {
                            error_log('SLA history log error: ' . $e->getMessage());
                        }
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Task resumed successfully',
                            'task_id' => $taskId,
                            'status' => 'in_progress',
                            'pause_duration' => $newTotalPause
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
                    
                    $db = Database::connect();
                    
                    // Ensure task_history table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS task_history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        task_id INT NOT NULL,
                        action VARCHAR(50) NOT NULL,
                        old_value TEXT,
                        new_value TEXT,
                        notes TEXT,
                        created_by INT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_task_id (task_id)
                    )");
                    
                    // Get current task data from daily_tasks
                    $stmt = $db->prepare("SELECT dt.*, t.id as original_task_id FROM daily_tasks dt LEFT JOIN tasks t ON dt.task_id = t.id WHERE dt.id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$dailyTask) {
                        throw new Exception('Task not found');
                    }
                    
                    $oldProgress = $dailyTask['completed_percentage'] ?? 0;
                    $oldStatus = $dailyTask['status'];
                    
                    // Determine final status based on progress
                    if ($progress >= 100) {
                        $finalStatus = 'completed';
                    } elseif ($progress > 0) {
                        $finalStatus = 'in_progress';
                    } else {
                        $finalStatus = 'assigned';
                    }
                    
                    // Update daily_tasks table
                    $stmt = $db->prepare("UPDATE daily_tasks SET completed_percentage = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $result1 = $stmt->execute([$progress, $finalStatus, $taskId]);
                    
                    // Update original tasks table if linked
                    $result2 = true;
                    if ($dailyTask['original_task_id']) {
                        $stmt = $db->prepare("UPDATE tasks SET progress = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $result2 = $stmt->execute([$progress, $finalStatus, $dailyTask['original_task_id']]);
                        
                        // Log to task history
                        if ($oldProgress != $progress) {
                            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'progress_updated', ?, ?, ?, ?)");
                            $stmt->execute([$dailyTask['original_task_id'], $oldProgress . '%', $progress . '%', $reason ?: 'Progress updated via daily planner', $userId]);
                        }
                        if ($oldStatus !== $finalStatus) {
                            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'status_changed', ?, ?, ?, ?)");
                            $stmt->execute([$dailyTask['original_task_id'], $oldStatus, $finalStatus, $reason ?: 'Status updated via daily planner', $userId]);
                        }
                    }
                    
                    // Log to daily planner history
                    $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, notes) VALUES (?, 'progress_updated', NOW(), ?)");
                    $stmt->execute([$taskId, "Progress updated from {$oldProgress}% to {$progress}%. {$reason}"]);
                    
                    $success = $result1 && $result2;
                    echo json_encode([
                        'success' => $success, 
                        'message' => $success ? 'Progress updated successfully' : 'Failed to update progress',
                        'progress' => $progress,
                        'status' => $finalStatus,
                        'old_progress' => $oldProgress,
                        'synced_to_tasks' => (bool)$dailyTask['original_task_id']
                    ]);
                    break;
                    
                case 'postpone':
                    $taskId = $input['task_id'] ?? null;
                    $newDate = $input['new_date'] ?? null;
                    $reason = $input['reason'] ?? 'No reason provided';
                    
                    if (!$taskId || !$newDate) {
                        throw new Exception('Task ID and new date required');
                    }
                    
                    $result = $planner->postponeTask($taskId, $userId, $newDate);
                    
                    if ($result) {
                        // Get updated daily stats to return to frontend
                        $currentDate = date('Y-m-d');
                        $stats = $planner->getDailyStats($userId, $currentDate);
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Task postponed successfully',
                            'new_date' => $newDate,
                            'task_id' => $taskId,
                            'updated_stats' => $stats,
                            'postponed_count' => $stats['postponed_tasks']
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to postpone task']);
                    }
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
                        WHERE dt.id = ?
                    ");
                    $stmt->execute([$taskId]);
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
                            'pause_duration' => $task['pause_duration'] ?? 0,
                            'is_late' => $isLate,
                            'late_seconds' => $lateTime,
                            'start_time' => $task['start_time'],
                            'sla_end_time' => $task['sla_end_time']
                        ]);
                    } else {
                        throw new Exception('Task not found');
                    }
                    break;
                    
                case 'sla-dashboard':
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $requestedUserId = $_GET['user_id'] ?? $userId;
                    $db = Database::connect();
                    
                    // Ensure current user session
                    if (!$userId) {
                        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                        break;
                    }
                    
                    // Security: Only allow users to see their own data (unless admin)
                    if ($requestedUserId != $userId && ($_SESSION['role'] ?? 'user') !== 'admin') {
                        $requestedUserId = $userId;
                    }
                    
                    // Get ONLY the specified user's daily planner tasks
                    $stmt = $db->prepare("
                        SELECT dt.*, COALESCE(t.sla_hours, 1.0) as sla_hours
                        FROM daily_tasks dt 
                        LEFT JOIN tasks t ON dt.task_id = t.id
                        WHERE dt.user_id = ? AND dt.scheduled_date = ?
                        ORDER BY dt.id
                    ");
                    $stmt->execute([$requestedUserId, $date]);
                    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Log for debugging
                    error_log("SLA Dashboard: User {$requestedUserId}, Date {$date}, Found " . count($tasks) . " tasks");
                    
                    $totalSla = 0;
                    $totalActive = 0;
                    $totalPause = 0;
                    $completed = 0;
                    $inProgress = 0;
                    $postponed = 0;
                    $now = time();
                    
                    foreach ($tasks as $task) {
                        // SLA calculation - ensure we have a valid SLA
                        $slaHours = floatval($task['sla_hours']);
                        if ($slaHours <= 0) $slaHours = 1.0; // Default to 1 hour
                        $totalSla += $slaHours * 3600;
                        
                        // Active time (including current session)
                        $activeTime = intval($task['active_seconds'] ?? 0);
                        if ($task['status'] === 'in_progress' && $task['start_time']) {
                            $lastActive = $task['resume_time'] ?: $task['start_time'];
                            $activeTime += $now - strtotime($lastActive);
                        }
                        $totalActive += $activeTime;
                        
                        // Pause time
                        $totalPause += intval($task['pause_duration'] ?? 0);
                        
                        // Count by status
                        switch ($task['status']) {
                            case 'completed': $completed++; break;
                            case 'in_progress': $inProgress++; break;
                            case 'postponed': $postponed++; break;
                        }
                    }
                    
                    $remaining = max(0, $totalSla - $totalActive);
                    $completionRate = count($tasks) > 0 ? ($completed / count($tasks)) * 100 : 0;
                    
                    // Validate data consistency
                    $response = [
                        'success' => true,
                        'sla_total_seconds' => max(0, $totalSla),
                        'active_seconds' => max(0, $totalActive),
                        'remaining_seconds' => max(0, $remaining),
                        'pause_seconds' => max(0, $totalPause),
                        'completion_rate' => round($completionRate, 1),
                        'total_tasks' => count($tasks),
                        'completed_tasks' => $completed,
                        'in_progress_tasks' => $inProgress,
                        'postponed_tasks' => $postponed,
                        'user_specific' => true,
                        'current_user_id' => $requestedUserId,
                        'session_user_id' => $userId,
                        'date' => $date,
                        'timestamp' => time(),
                        'message' => 'User-specific SLA data for ' . count($tasks) . ' tasks'
                    ];
                    
                    // Ensure we have valid data before sending
                    if ($response['sla_total_seconds'] >= 0 && $response['total_tasks'] >= 0) {
                        echo json_encode($response);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid SLA data calculated',
                            'debug' => $response
                        ]);
                    }
                    break;
                    
                case 'stats':
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $stats = $planner->getDailyStats($userId, $date);
                    echo json_encode(['success' => true, 'stats' => $stats]);
                    break;
                    
                case 'debug-stats':
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $db = Database::connect();
                    
                    // Get detailed breakdown
                    $stmt = $db->prepare("
                        SELECT status, COUNT(*) as count, GROUP_CONCAT(title SEPARATOR ', ') as tasks
                        FROM daily_tasks 
                        WHERE user_id = ? AND (scheduled_date = ? OR (status = 'postponed' AND postponed_from_date = ?))
                        GROUP BY status
                    ");
                    $stmt->execute([$userId, $date]);
                    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $stats = $planner->getDailyStats($userId, $date);
                    echo json_encode([
                        'success' => true, 
                        'date' => $date,
                        'user_id' => $userId,
                        'stats' => $stats,
                        'breakdown' => $breakdown
                    ]);
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