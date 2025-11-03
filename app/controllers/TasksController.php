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
            // Initialize with null but create fallback methods
            $this->taskModel = null;
            $this->userModel = null;
        }
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC");
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tasks)) {
                $tasks = $this->getStaticTasks();
            }
        } catch (Exception $e) {
            error_log("Task fetch error: " . $e->getMessage());
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
            return $this->store();
        }
        
        $users = $this->getActiveUsers();
        error_log('Users for dropdown: ' . json_encode($users));
        
        $data = [
            'users' => $users,
            'active_page' => 'tasks'
        ];
        $this->view('tasks/create', $data);
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                return [
                    ['id' => 1, 'name' => 'System Owner', 'email' => 'owner@ergon.com', 'role' => 'owner'],
                    ['id' => 2, 'name' => 'Admin User', 'email' => 'admin@ergon.com', 'role' => 'admin']
                ];
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [
                ['id' => 1, 'name' => 'System Owner', 'email' => 'owner@ergon.com', 'role' => 'owner']
            ];
        }
    }
    
    public function store() {
        AuthMiddleware::requireAuth();
        
        $taskData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'assigned_by' => $_SESSION['user_id'],
            'assigned_to' => intval($_POST['assigned_to'] ?? 0),
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null
        ];
        
        error_log('Task store data: ' . json_encode($taskData));
        error_log('POST deadline: ' . ($_POST['deadline'] ?? 'empty'));
        
        if (empty($taskData['title']) || $taskData['assigned_to'] <= 0) {
            header('Location: /ergon/tasks/create?error=Title and assigned user are required');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, deadline, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'assigned', NOW())");
            $result = $stmt->execute([$taskData['title'], $taskData['description'], $taskData['assigned_by'], $taskData['assigned_to'], $taskData['priority'], $taskData['deadline']]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                error_log('Task created with ID: ' . $taskId . ', deadline: ' . ($taskData['deadline'] ?? 'null'));
                header('Location: /ergon/tasks?success=Task created successfully');
            } else {
                error_log('Task creation failed: ' . implode(', ', $stmt->errorInfo()));
                header('Location: /ergon/tasks/create?error=Failed to create task');
            }
        } catch (Exception $e) {
            error_log('Task creation exception: ' . $e->getMessage());
            header('Location: /ergon/tasks/create?error=Task creation failed');
        }
        exit;
    }
    
    public function edit($id) {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskData = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'assigned_to' => intval($_POST['assigned_to'] ?? 0),
                'priority' => $_POST['priority'] ?? 'medium',
                'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                'status' => $_POST['status'] ?? 'assigned'
            ];
            
            if (empty($taskData['title']) || $taskData['assigned_to'] <= 0) {
                header('Location: /ergon/tasks/edit/' . $id . '?error=Title and assigned user are required');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, priority=?, deadline=?, status=? WHERE id=?");
                $result = $stmt->execute([$taskData['title'], $taskData['description'], $taskData['assigned_to'], $taskData['priority'], $taskData['deadline'], $taskData['status'], $id]);
                
                if ($result) {
                    header('Location: /ergon/tasks?success=Task updated successfully');
                } else {
                    header('Location: /ergon/tasks/edit/' . $id . '?error=Failed to update task');
                }
            } catch (Exception $e) {
                header('Location: /ergon/tasks/edit/' . $id . '?error=Update failed');
            }
            exit;
        }
        
        // Get task data
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                header('Location: /ergon/tasks?error=Task not found');
                exit;
            }
            
            $users = $this->getActiveUsers();
            
            $data = [
                'task' => $task,
                'users' => $users,
                'active_page' => 'tasks'
            ];
            
            $this->view('tasks/edit', $data);
        } catch (Exception $e) {
            header('Location: /ergon/tasks?error=Failed to load task');
            exit;
        }
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
    
    public function viewTask($id) {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Always use direct database query with proper JOIN
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log('Task view debug - ID: ' . $id);
            error_log('Task data: ' . json_encode($task));
            
            if (!$task) {
                header('Location: /ergon/tasks?error=not_found');
                exit;
            }
            
            $data = [
                'task' => $task,
                'active_page' => 'tasks'
            ];
            
            $this->view('tasks/view', $data);
        } catch (Exception $e) {
            error_log('Task view error: ' . $e->getMessage());
            header('Location: /ergon/tasks?error=view_failed');
            exit;
        }
    }
    
    public function delete($id) {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            if ($this->taskModel !== null) {
                $result = $this->taskModel->delete($id);
            } else {
                // Fallback direct database delete
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
                $result = $stmt->execute([$id]);
            }
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            error_log('Task delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
    
    private function ensureTasksTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                assigned_by INT DEFAULT NULL,
                assigned_to INT DEFAULT NULL,
                task_type VARCHAR(50) DEFAULT 'ad-hoc',
                priority VARCHAR(20) DEFAULT 'medium',
                deadline DATE DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'assigned',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (Exception $e) {
            error_log('ensureTasksTable error: ' . $e->getMessage());
        }
    }
}
?>
