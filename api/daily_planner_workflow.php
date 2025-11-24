<?php
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
$action = $_GET['action'] ?? null;
$task_id = $input['task_id'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

// Only require task_id for specific actions
if (in_array($action, ['start', 'pause', 'resume']) && !$task_id) {
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
                    'success' => true,
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
            echo json_encode([
                'success' => true,
                'sla_total_seconds' => 0,
                'active_seconds' => 0,
                'remaining_seconds' => 0,
                'pause_seconds' => 0,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'postponed_tasks' => 0
            ]);
            break;
            
        case 'timer':
            $taskId = $_GET['task_id'] ?? null;
            echo json_encode([
                'success' => true,
                'active_seconds' => 0,
                'remaining_seconds' => 3600,
                'status' => 'not_started'
            ]);
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