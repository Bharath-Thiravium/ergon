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
        try {
            $this->taskModel = new Task();
            $this->userModel = new User();
        } catch (Exception $e) {
            error_log("TasksController init error: " . $e->getMessage());
            $this->taskModel = null;
            $this->userModel = null;
        }
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $title = 'Tasks';
        $active_page = 'tasks';
        
        // Try to get tasks from database, fallback to static data
        if ($this->taskModel !== null) {
            try {
                $tasks = $this->taskModel->getAll();
                if (empty($tasks)) {
                    $tasks = $this->getStaticTasks();
                }
            } catch (Exception $e) {
                error_log("Task fetch error: " . $e->getMessage());
                $tasks = $this->getStaticTasks();
            }
        } else {
            $tasks = $this->getStaticTasks();
        }
        
        $data = ['tasks' => $tasks, 'active_page' => 'tasks'];
        $this->view('tasks/index', $data);
    }
    
    private function getStaticTasks() {
        return [
            ['id' => 1, 'title' => 'Database Setup', 'assigned_user' => 'John Doe', 'priority' => 'high', 'status' => 'in_progress', 'due_date' => '2024-01-25'],
            ['id' => 2, 'title' => 'UI Design', 'assigned_user' => 'Jane Smith', 'priority' => 'medium', 'status' => 'pending', 'due_date' => '2024-01-30'],
            ['id' => 3, 'title' => 'API Development', 'assigned_user' => 'Mike Johnson', 'priority' => 'high', 'status' => 'assigned', 'due_date' => '2024-02-05']
        ];
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
                header('Location: /ergon/tasks?success=created');
                exit;
            }
        }
        
        // Get users with fallback
        try {
            if ($this->userModel !== null) {
                $users = $this->userModel->getAll();
            } else {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE status = 'active' ORDER BY name");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            $users = [];
        }
        
        // Get departments for task assignment
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $departments = [];
        }
        
        $data = [
            'users' => $users,
            'departments' => $departments,
            'active_page' => 'tasks'
        ];
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
                header('Location: /ergon/tasks?success=created');
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
                header('Location: /ergon/tasks?success=updated');
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
