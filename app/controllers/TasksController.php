<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class TasksController extends Controller {
    private $taskModel;
    private $userModel;
    
    public function __construct() {
        $this->taskModel = new Task();
        $this->userModel = new User();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $tasks = $this->taskModel->getAllTasks();
        $users = $this->userModel->getAll();
        $stats = $this->taskModel->getTaskStats();
        
        $data = [
            'tasks' => $tasks,
            'users' => $users,
            'stats' => $stats
        ];
        
        $this->view('tasks/index', $data);
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                die('CSRF validation failed');
            }
            
            $taskData = [
                'title' => Security::sanitizeString($_POST['title']),
                'description' => Security::sanitizeString($_POST['description'], 1000),
                'assigned_by' => $_SESSION['user_id'],
                'assigned_to' => Security::validateInt($_POST['assigned_to']),
                'task_type' => Security::sanitizeString($_POST['task_type']),
                'priority' => Security::sanitizeString($_POST['priority']),
                'deadline' => $_POST['deadline']
            ];
            
            $result = $this->taskModel->create($taskData);
            if ($result) {
                header('Location: /ergon/tasks?success=created');
                exit;
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users];
        $this->view('tasks/create', $data);
    }
    
    public function update($taskId) {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                die('CSRF validation failed');
            }
            
            $progress = Security::validateInt($_POST['progress'], 0, 100);
            $comment = Security::sanitizeString($_POST['comment'] ?? '', 500);
            
            $result = $this->taskModel->updateProgress($taskId, $_SESSION['user_id'], $progress, $comment);
            if ($result) {
                header('Location: /ergon/tasks?success=updated');
                exit;
            }
        }
        
        $task = $this->taskModel->getTaskById($taskId);
        $updates = $this->taskModel->getTaskUpdates($taskId);
        
        $data = [
            'task' => $task,
            'updates' => $updates
        ];
        
        $this->view('tasks/update', $data);
    }
    
    public function calendar() {
        $tasks = $this->taskModel->getTasksForCalendar();
        $data = ['tasks' => $tasks];
        $this->view('tasks/calendar', $data);
    }
    
    public function overdue() {
        $tasks = $this->taskModel->getOverdueTasks();
        $data = ['tasks' => $tasks];
        $this->view('tasks/overdue', $data);
    }
    
    public function slaBreaches() {
        $tasks = $this->taskModel->getSLABreaches();
        $data = ['tasks' => $tasks];
        $this->view('tasks/sla_breaches', $data);
    }
    
    public function getVelocity($userId) {
        header('Content-Type: application/json');
        $velocity = $this->taskModel->getTaskVelocity($userId);
        echo json_encode(['velocity' => $velocity]);
    }
    
    public function getProductivity($userId) {
        header('Content-Type: application/json');
        $productivity = $this->taskModel->getProductivityScore($userId);
        echo json_encode($productivity);
    }
    
    public function bulkCreate() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'CSRF validation failed']);
                return;
            }
            
            $tasks = json_decode($_POST['tasks'], true);
            $result = $this->taskModel->createBulkTasks($tasks);
            echo json_encode(['success' => $result]);
        }
    }
    
    public function getSubtasks($parentId) {
        header('Content-Type: application/json');
        $subtasks = $this->taskModel->getSubtasks($parentId);
        echo json_encode(['subtasks' => $subtasks]);
    }
}
?>