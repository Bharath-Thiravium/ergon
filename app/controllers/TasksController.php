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
                $tasks = [];
            }
        } catch (Exception $e) {
            error_log("Task fetch error: " . $e->getMessage());
            $tasks = $this->getStaticTasks();
        }
        
        $data = ['tasks' => $tasks, 'active_page' => 'tasks'];
        $this->view('tasks/index', $data);
    }
    

    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        $users = $this->getActiveUsers();
        $departments = $this->getDepartments();
        
        $data = [
            'users' => $users,
            'departments' => $departments,
            'active_page' => 'tasks'
        ];
        $this->view('tasks/create', $data);
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            // If user role is 'user', only return themselves
            if (($_SESSION['role'] ?? '') === 'user') {
                $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($users)) {
                    return [['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name'] ?? 'Current User', 'email' => '', 'role' => 'user']];
                }
                return $users;
            }
            
            // For admin/owner, return all users
            $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                return [];
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    
    public function store() {
        AuthMiddleware::requireAuth();
        
        $taskData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'assigned_by' => $_SESSION['user_id'],
            'assigned_to' => intval($_POST['assigned_to'] ?? 0),
            'task_type' => $_POST['task_type'] ?? 'ad-hoc',
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
            'status' => $_POST['status'] ?? 'assigned',
            'progress' => intval($_POST['progress'] ?? 0),

            'sla_hours' => intval($_POST['sla_hours'] ?? 24),
            'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'task_category' => trim($_POST['task_category'] ?? '')
        ];
        
        error_log('Task store data: ' . json_encode($taskData));
        
        if (empty($taskData['title']) || $taskData['assigned_to'] <= 0) {
            header('Location: /ergon/tasks/create?error=Title and assigned user are required');
            exit;
        }
        
        // Validate progress range
        if ($taskData['progress'] < 0 || $taskData['progress'] > 100) {
            header('Location: /ergon/tasks/create?error=Progress must be between 0 and 100');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, status, progress, sla_hours, department_id, task_category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $taskData['title'], 
                $taskData['description'], 
                $taskData['assigned_by'], 
                $taskData['assigned_to'], 
                $taskData['task_type'],
                $taskData['priority'], 
                $taskData['deadline'],
                $taskData['status'],
                $taskData['progress'],
                $taskData['sla_hours'],
                $taskData['department_id'],
                $taskData['task_category']
            ]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                // Create notifications for task assignment
                require_once __DIR__ . '/../helpers/NotificationHelper.php';
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$taskData['assigned_to']]);
                $assignedUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($assignedUser) {
                    // Notify assigned user
                    NotificationHelper::notifyUser(
                        $taskData['assigned_by'],
                        $taskData['assigned_to'],
                        'task',
                        'assigned',
                        "You have been assigned a new task: {$taskData['title']}",
                        $taskId
                    );
                    
                    // Notify owners about new task creation
                    NotificationHelper::notifyOwners(
                        $taskData['assigned_by'],
                        'task',
                        'created',
                        "New task '{$taskData['title']}' assigned to {$assignedUser['name']}",
                        $taskId
                    );
                }
                
                // Auto-create followup if task category contains "follow-up"
                if (!empty($taskData['task_category']) && (stripos($taskData['task_category'], 'follow') !== false || stripos($taskData['task_category'], 'Follow') !== false)) {
                    error_log('Creating auto-followup for task category: ' . $taskData['task_category']);
                    $this->createAutoFollowup($db, $taskId, $taskData, $_POST);
                } else {
                    error_log('No auto-followup created. Category: ' . ($taskData['task_category'] ?? 'empty'));
                }
                
                error_log('Task created with ID: ' . $taskId . ', type: ' . $taskData['task_type'] . ', progress: ' . $taskData['progress'] . '%');
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
                'task_type' => $_POST['task_type'] ?? 'ad-hoc',
                'priority' => $_POST['priority'] ?? 'medium',
                'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                'status' => $_POST['status'] ?? 'assigned',
                'progress' => intval($_POST['progress'] ?? 0),
                'sla_hours' => intval($_POST['sla_hours'] ?? 24),
                'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                'task_category' => trim($_POST['task_category'] ?? '')
            ];
            
            if (empty($taskData['title']) || $taskData['assigned_to'] <= 0) {
                header('Location: /ergon/tasks/edit/' . $id . '?error=Title and assigned user are required');
                exit;
            }
            
            // Validate progress range
            if ($taskData['progress'] < 0 || $taskData['progress'] > 100) {
                header('Location: /ergon/tasks/edit/' . $id . '?error=Progress must be between 0 and 100');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensureTasksTable($db);
                
                $stmt = $db->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, task_type=?, priority=?, deadline=?, status=?, progress=?, sla_hours=?, department_id=?, task_category=?, updated_at=NOW() WHERE id=?");
                $result = $stmt->execute([
                    $taskData['title'], 
                    $taskData['description'], 
                    $taskData['assigned_to'], 
                    $taskData['task_type'],
                    $taskData['priority'], 
                    $taskData['deadline'], 
                    $taskData['status'],
                    $taskData['progress'],
                    $taskData['sla_hours'],
                    $taskData['department_id'],
                    $taskData['task_category'],
                    $id
                ]);
                
                if ($result) {
                    error_log('Task updated with ID: ' . $id . ', progress: ' . $taskData['progress'] . '%');
                    header('Location: /ergon/tasks?success=Task updated successfully');
                } else {
                    error_log('Task update failed: ' . implode(', ', $stmt->errorInfo()));
                    header('Location: /ergon/tasks/edit/' . $id . '?error=Failed to update task');
                }
            } catch (Exception $e) {
                error_log('Task update exception: ' . $e->getMessage());
                header('Location: /ergon/tasks/edit/' . $id . '?error=Update failed');
            }
            exit;
        }
        
        // Get task data
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            // Get task with department name
            $stmt = $db->prepare("SELECT t.*, d.name as department_name FROM tasks t LEFT JOIN departments d ON t.department_id = d.id WHERE t.id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                header('Location: /ergon/tasks?error=Task not found');
                exit;
            }
            
            $users = $this->getActiveUsers();
            $departments = $this->getDepartments();
            
            $data = [
                'task' => $task,
                'users' => $users,
                'departments' => $departments,
                'active_page' => 'tasks'
            ];
            
            $this->view('tasks/edit', $data);
        } catch (Exception $e) {
            error_log('Task edit load error: ' . $e->getMessage());
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
            
            // Always use direct database query with proper JOINs
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user, d.name as department_name, ub.name as assigned_by_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN departments d ON t.department_id = d.id LEFT JOIN users ub ON t.assigned_by = ub.id WHERE t.id = ?");
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
    
    public function updateStatus() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = intval($input['task_id'] ?? 0);
        $progress = intval($input['progress'] ?? 0);
        $status = $input['status'] ?? 'assigned';
        
        if (!$taskId || $progress < 0 || $progress > 100) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("UPDATE tasks SET progress = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$progress, $status, $taskId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update task status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function delete($id) {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Task deleted successfully' : 'Delete failed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    private function getDepartments() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure departments table exists
            $db->exec("CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                head_id INT NULL,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no departments exist, create default ones
            if (empty($departments)) {
                $defaultDepts = [
                    'Human Resources',
                    'Information Technology', 
                    'Finance',
                    'Marketing',
                    'Operations',
                    'Sales'
                ];
                
                $insertStmt = $db->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                foreach ($defaultDepts as $dept) {
                    $insertStmt->execute([$dept, 'Default department']);
                }
                
                // Fetch again after creating defaults
                $stmt->execute();
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $departments;
        } catch (Exception $e) {
            error_log('Error fetching departments: ' . $e->getMessage());
            return [];
        }
    }
    
    private function createAutoFollowup($db, $taskId, $taskData, $postData) {
        try {
            // Ensure followups table exists
            $this->ensureFollowupsTable($db);
            
            // Use follow-up specific data if provided, otherwise use task data
            $followupDate = !empty($postData['followup_date']) ? $postData['followup_date'] : 
                          (!empty($taskData['deadline']) ? date('Y-m-d', strtotime($taskData['deadline'])) : date('Y-m-d', strtotime('+1 day')));
            
            $followupTime = !empty($postData['followup_time']) ? $postData['followup_time'] : '09:00:00';
            
            // Create followup title
            $followupTitle = 'Follow-up: ' . $taskData['title'];
            if (!empty($postData['company_name'])) {
                $followupTitle = 'Follow-up: ' . $postData['company_name'] . ' - ' . $taskData['title'];
            }
            
            // Create followup description
            $followupDesc = 'Auto-created follow-up for task: ' . $taskData['title'];
            if (!empty($taskData['description'])) {
                $followupDesc .= "\n\nTask Description: " . $taskData['description'];
            }
            if (!empty($postData['description'])) {
                $followupDesc = $postData['description'];
            }
            
            // Create followup with extended fields
            $stmt = $db->prepare("
                INSERT INTO followups (
                    user_id, task_id, title, description, company_name, contact_person, 
                    contact_phone, project_name, follow_up_date, reminder_time, 
                    original_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $taskData['assigned_to'],
                $taskId,
                $followupTitle,
                $followupDesc,
                $postData['company_name'] ?? '',
                $postData['contact_person'] ?? '',
                $postData['contact_phone'] ?? '',
                $postData['project_name'] ?? '',
                $followupDate,
                $followupTime,
                $followupDate
            ]);
            
            error_log('Follow-up creation attempt - User ID: ' . $taskData['assigned_to'] . ', Title: ' . $followupTitle);
            
            if ($result) {
                $followupId = $db->lastInsertId();
                error_log('Enhanced follow-up created successfully with ID: ' . $followupId . ' for task ID: ' . $taskId . ' assigned to user: ' . $taskData['assigned_to']);
                
                // Log to followup history
                try {
                    $historyStmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, 'created', NULL, ?, ?, ?)");
                    $historyStmt->execute([
                        $followupId,
                        'Auto-created from task',
                        'Follow-up automatically created from task: ' . $taskData['title'],
                        $taskData['assigned_by'] ?? $taskData['assigned_to']
                    ]);
                } catch (Exception $historyError) {
                    error_log('Failed to log followup history: ' . $historyError->getMessage());
                }
            } else {
                error_log('Failed to create follow-up: ' . implode(', ', $stmt->errorInfo()));
            }
        } catch (Exception $e) {
            error_log('Auto-followup creation failed: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    private function ensureFollowupsTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                title VARCHAR(255) NOT NULL,
                company_name VARCHAR(255),
                contact_person VARCHAR(255),
                contact_phone VARCHAR(20),
                project_name VARCHAR(255),
                follow_up_date DATE NOT NULL,
                original_date DATE,
                reminder_time TIME NULL,
                description TEXT,
                status ENUM('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
                completed_at TIMESTAMP NULL,
                reminder_sent BOOLEAN DEFAULT FALSE,
                next_reminder DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_task_id (task_id),
                INDEX idx_follow_date (follow_up_date),
                INDEX idx_status (status)
            )");
            
            // Add task_id column if it doesn't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('task_id', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER user_id");
                    $db->exec("ALTER TABLE followups ADD INDEX idx_task_id (task_id)");
                }
            } catch (Exception $e) {
                error_log('Task ID column addition error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log('ensureFollowupsTable error: ' . $e->getMessage());
        }
    }
    
    private function ensureTasksTable($db) {
        try {
            // Create tasks table with all required columns
            $db->exec("CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                assigned_by INT DEFAULT NULL,
                assigned_to INT DEFAULT NULL,
                task_type ENUM('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
                priority ENUM('low','medium','high') DEFAULT 'medium',
                deadline DATETIME DEFAULT NULL,
                progress INT DEFAULT 0,
                status ENUM('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
                due_date DATE DEFAULT NULL,
                depends_on_task_id INT DEFAULT NULL,
                sla_hours INT DEFAULT 24,
                department_id INT DEFAULT NULL,
                task_category VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Check if department_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'department_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE tasks ADD COLUMN department_id INT DEFAULT NULL");
                error_log('Added department_id column to tasks table');
            }
            
            // Check if task_category column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'task_category'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL");
                error_log('Added task_category column to tasks table');
            }
        } catch (Exception $e) {
            error_log('ensureTasksTable error: ' . $e->getMessage());
        }
    }
}
?>
