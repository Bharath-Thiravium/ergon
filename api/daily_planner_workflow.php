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
                        throw new Exception('Task ID required');
                    }
                    
                    $result = $planner->startTask($taskId, $userId);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task started' : 'Failed to start task']);
                    break;
                    
                case 'pause':
                    $taskId = $input['task_id'] ?? null;
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    $result = $planner->pauseTask($taskId, $userId);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task paused' : 'Failed to pause task']);
                    break;
                    
                case 'resume':
                    $taskId = $input['task_id'] ?? null;
                    if (!$taskId) {
                        throw new Exception('Task ID required');
                    }
                    
                    $result = $planner->resumeTask($taskId, $userId);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Task resumed' : 'Failed to resume task']);
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
                        SELECT active_seconds, start_time, resume_time, status
                        FROM daily_tasks 
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$taskId, $userId]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($task) {
                        $currentActiveTime = 0;
                        if ($task['status'] === 'in_progress') {
                            $startTime = $task['resume_time'] ?: $task['start_time'];
                            if ($startTime) {
                                $currentActiveTime = time() - strtotime($startTime);
                            }
                        }
                        
                        echo json_encode([
                            'success' => true,
                            'active_seconds' => $task['active_seconds'] + $currentActiveTime,
                            'status' => $task['status']
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