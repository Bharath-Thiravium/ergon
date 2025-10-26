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
        $this->requireAuth();
        
        $title = 'Tasks';
        $active_page = 'tasks';
        
        $tasks = [
            ['id' => 1, 'title' => 'Database Setup', 'assigned_user' => 'John Doe', 'priority' => 'high', 'status' => 'in_progress', 'due_date' => '2024-01-25'],
            ['id' => 2, 'title' => 'UI Design', 'assigned_user' => 'Jane Smith', 'priority' => 'medium', 'status' => 'pending', 'due_date' => '2024-01-30']
        ];
        
        include __DIR__ . '/../../views/tasks/index.php';
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                header('Location: /ergon/public/tasks?success=created');
                exit;
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users, 'active_page' => 'tasks'];
        $this->view('tasks/create', $data);
    }
    
    public function store() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskData = [
                'title' => Security::sanitizeString($_POST['title']),
                'description' => Security::sanitizeString($_POST['description'], 1000),
                'assigned_by' => $_SESSION['user_id'],
                'assigned_to' => Security::validateInt($_POST['assigned_to']),
                'task_type' => Security::sanitizeString($_POST['task_type'] ?? 'task'),
                'priority' => Security::sanitizeString($_POST['priority']),
                'deadline' => $_POST['deadline']
            ];
            
            $result = $this->taskModel->create($taskData);
            if ($result) {
                header('Location: /ergon/public/tasks?success=created');
                exit;
            }
        }
        
        $this->create();
    }
    
    public function update($taskId) {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                die('CSRF validation failed');
            }
            
            $progress = Security::validateInt($_POST['progress'], 0, 100);
            $comment = Security::sanitizeString($_POST['comment'] ?? '', 500);
            
            $result = $this->taskModel->updateProgress($taskId, $_SESSION['user_id'], $progress, $comment);
            if ($result) {
                header('Location: /ergon/public/tasks?success=updated');
                exit;
            }
        }
        
        $task = $this->taskModel->getTaskById($taskId);
        $updates = $this->taskModel->getTaskUpdates($taskId);
        
        $data = [
            'task' => $task,
            'updates' => $updates,
            'active_page' => 'tasks'
        ];
        
        $this->view('tasks/update', $data);
    }
    
    public function calendar() {
        $tasks = $this->taskModel->getTasksForCalendar();
        $data = ['tasks' => $tasks, 'active_page' => 'tasks'];
        $this->view('tasks/calendar', $data);
    }
    
    public function overdue() {
        $tasks = $this->taskModel->getOverdueTasks();
        $data = ['tasks' => $tasks, 'active_page' => 'tasks'];
        $this->view('tasks/overdue', $data);
    }
    
    public function bulkCreate() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
