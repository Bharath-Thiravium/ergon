<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/DailyPlanner.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    switch ($action) {
        case 'sla-dashboard':
            $date = $_GET['date'] ?? date('Y-m-d');
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception('Invalid date format');
            }
            
            // Prevent future dates
            if ($date > date('Y-m-d')) {
                throw new Exception('Cannot access future dates');
            }
            
            $stats = $planner->getDailyStats($userId, $date);
            
            // Calculate SLA totals
            $stmt = $db->prepare("
                SELECT 
                    SUM(COALESCE(t.sla_hours, 1) * 3600) as sla_total_seconds,
                    SUM(dt.active_seconds) as active_seconds,
                    SUM(dt.pause_duration) as pause_seconds,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN dt.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN dt.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN dt.status = 'postponed' THEN 1 ELSE 0 END) as postponed_tasks
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.user_id = ? AND dt.scheduled_date = ?
            ");
            $stmt->execute([$userId, $date]);
            $slaData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $slaTotal = $slaData['sla_total_seconds'] ?? 0;
            $activeSeconds = $slaData['active_seconds'] ?? 0;
            $pauseSeconds = $slaData['pause_seconds'] ?? 0;
            $remainingSeconds = max(0, $slaTotal - $activeSeconds);
            
            echo json_encode([
                'success' => true,
                'user_specific' => true,
                'current_user_id' => $userId,
                'sla_total_seconds' => $slaTotal,
                'active_seconds' => $activeSeconds,
                'remaining_seconds' => $remainingSeconds,
                'pause_seconds' => $pauseSeconds,
                'total_tasks' => $slaData['total_tasks'] ?? 0,
                'completed_tasks' => $slaData['completed_tasks'] ?? 0,
                'in_progress_tasks' => $slaData['in_progress_tasks'] ?? 0,
                'postponed_tasks' => $slaData['postponed_tasks'] ?? 0,
                'completion_rate' => $slaData['total_tasks'] > 0 ? 
                    round(($slaData['completed_tasks'] / $slaData['total_tasks']) * 100, 1) : 0
            ]);
            break;
            
        case 'timer':
            $taskId = $_GET['task_id'] ?? null;
            if (!$taskId) {
                throw new Exception('Task ID required');
            }
            
            $stmt = $db->prepare("
                SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours
                FROM daily_tasks dt
                LEFT JOIN tasks t ON dt.task_id = t.id
                WHERE dt.id = ?
            ");
            $stmt->execute([$taskId]);
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
            
            echo json_encode([
                'success' => true,
                'active_seconds' => $activeSeconds,
                'remaining_seconds' => $remainingSeconds,
                'pause_duration' => $pauseSeconds,
                'is_late' => $isLate,
                'late_seconds' => $lateSeconds,
                'sla_seconds' => $slaSeconds
            ]);
            break;
            
        case 'start':
            $taskId = json_decode(file_get_contents('php://input'), true)['task_id'] ?? null;
            if (!$taskId) {
                throw new Exception('Task ID required');
            }
            
            if ($planner->startTask($taskId, $userId)) {
                // Record in task history
                try {
                    // Ensure task_history table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS task_history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        task_id INT NOT NULL,
                        action VARCHAR(50) NOT NULL,
                        old_value TEXT,
                        new_value TEXT,
                        notes TEXT,
                        created_by INT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    $stmt = $db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($dailyTask && $dailyTask['original_task_id']) {
                        $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', ?)");
                        $stmt->execute([$dailyTask['original_task_id'], $userId]);
                    }
                } catch (Exception $e) {
                    error_log('Task history logging failed: ' . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Task started']);
            } else {
                throw new Exception('Failed to start task');
            }
            break;
            
        case 'pause':
            $taskId = json_decode(file_get_contents('php://input'), true)['task_id'] ?? null;
            if (!$taskId) {
                throw new Exception('Task ID required');
            }
            
            if ($planner->pauseTask($taskId, $userId)) {
                // Record in task history
                try {
                    $stmt = $db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($dailyTask && $dailyTask['original_task_id']) {
                        $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', ?)");
                        $stmt->execute([$dailyTask['original_task_id'], $userId]);
                    }
                } catch (Exception $e) {
                    error_log('Task history logging failed: ' . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Task paused', 'pause_start' => time()]);
            } else {
                throw new Exception('Failed to pause task');
            }
            break;
            
        case 'resume':
            $taskId = json_decode(file_get_contents('php://input'), true)['task_id'] ?? null;
            if (!$taskId) {
                throw new Exception('Task ID required');
            }
            
            if ($planner->resumeTask($taskId, $userId)) {
                // Record in task history
                try {
                    $stmt = $db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($dailyTask && $dailyTask['original_task_id']) {
                        $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', ?)");
                        $stmt->execute([$dailyTask['original_task_id'], $userId]);
                    }
                } catch (Exception $e) {
                    error_log('Task history logging failed: ' . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Task resumed']);
            } else {
                throw new Exception('Failed to resume task');
            }
            break;
            
        case 'update-progress':
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = $input['task_id'] ?? null;
            $progress = $input['progress'] ?? 100;
            $status = $input['status'] ?? 'completed';
            
            if (!$taskId) {
                throw new Exception('Task ID required');
            }
            
            if ($planner->updateTaskProgress($taskId, $userId, $progress, $status)) {
                // Record in task history
                try {
                    $stmt = $db->prepare("SELECT original_task_id FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($dailyTask && $dailyTask['original_task_id']) {
                        $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'progress_updated', '', ?, ?, ?)");
                        $stmt->execute([$dailyTask['original_task_id'], $progress . '%', 'Progress updated to ' . $progress . '% via Daily Planner', $userId]);
                        
                        if ($progress >= 100) {
                            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'status_changed', 'in_progress', 'completed', 'Task completed via Daily Planner', ?)");
                            $stmt->execute([$dailyTask['original_task_id'], $userId]);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Task history logging failed: ' . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Progress updated',
                    'progress' => $progress,
                    'status' => $status
                ]);
            } else {
                throw new Exception('Failed to update progress');
            }
            break;
            
        case 'postpone':
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = $input['task_id'] ?? null;
            $newDate = $input['new_date'] ?? null;
            $reason = $input['reason'] ?? 'Postponed via Daily Planner';
            
            if (!$taskId || !$newDate) {
                throw new Exception('Task ID and new date required');
            }
            
            if ($planner->postponeTask($taskId, $userId, $newDate)) {
                // Record in task history
                try {
                    $stmt = $db->prepare("SELECT original_task_id, scheduled_date FROM daily_tasks WHERE id = ?");
                    $stmt->execute([$taskId]);
                    $dailyTask = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($dailyTask && $dailyTask['original_task_id']) {
                        $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, 'postponed', ?, ?, ?, ?)");
                        $stmt->execute([$dailyTask['original_task_id'], $dailyTask['scheduled_date'], $newDate, $reason, $userId]);
                    }
                } catch (Exception $e) {
                    error_log('Task history logging failed: ' . $e->getMessage());
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
    error_log('Daily planner workflow API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}