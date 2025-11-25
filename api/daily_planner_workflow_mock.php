<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Mock database data
$mockTasks = [
    212 => ['id' => 212, 'status' => 'on_break', 'title' => 'Draft team meeting agenda'],
    209 => ['id' => 209, 'status' => 'in_progress', 'title' => 'Review Q3 Report'],
    210 => ['id' => 210, 'status' => 'on_break', 'title' => 'Prepare presentation slides']
];

// Get input
$rawInput = file_get_contents('php://input');
$requestInput = json_decode($rawInput, true) ?: [];

$action = $_GET['action'] ?? '';
$taskId = $requestInput['task_id'] ?? null;
$userId = $_SESSION['user_id'];

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'timer') {
    $token = $requestInput['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    switch ($action) {
        case 'pause':
            if (!isset($mockTasks[$taskId])) {
                throw new Exception('Task not found');
            }
            if ($mockTasks[$taskId]['status'] !== 'in_progress') {
                throw new Exception("Cannot pause task. Current status: {$mockTasks[$taskId]['status']}. Task must be 'in_progress' to pause.");
            }
            
            // Mock successful pause
            echo json_encode([
                'success' => true, 
                'message' => 'Task paused (MOCK)', 
                'pause_start' => time(),
                'new_status' => 'on_break'
            ]);
            break;
            
        case 'resume':
            if (!isset($mockTasks[$taskId])) {
                throw new Exception('Task not found');
            }
            if ($mockTasks[$taskId]['status'] !== 'on_break') {
                throw new Exception("Cannot resume task. Current status: {$mockTasks[$taskId]['status']}. Task must be 'on_break' to resume.");
            }
            
            // Mock successful resume
            echo json_encode([
                'success' => true, 
                'message' => 'Task resumed (MOCK)',
                'new_status' => 'in_progress'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'action' => $action,
            'task_id' => $taskId,
            'mock_status' => $mockTasks[$taskId]['status'] ?? 'not_found',
            'error' => $e->getMessage()
        ]
    ]);
}
?>