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
        $projects = $this->getProjects();
        
        $data = [
            'users' => $users,
            'departments' => $departments,
            'projects' => $projects,
            'active_page' => 'tasks'
        ];
        $this->view('tasks/create', $data);
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            // Return all users for task assignment (allow any user to assign tasks to others)
            $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                // Fallback to current user if no users found
                return [['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name'] ?? 'Current User', 'email' => '', 'role' => 'user']];
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
            'planned_date' => !empty($_POST['planned_date']) ? $_POST['planned_date'] : null,
            'status' => $_POST['status'] ?? 'assigned',
            'progress' => intval($_POST['progress'] ?? 0),
            'sla_hours' => floatval($_POST['sla_hours'] ?? 0.25),
            'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'task_category' => trim($_POST['task_category'] ?? ''),
            'project_id' => !empty($_POST['project_id']) ? intval($_POST['project_id']) : null
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
            
            $followupRequired = !empty($_POST['followup_required']) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, planned_date, status, progress, sla_hours, department_id, task_category, project_id, followup_required, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $taskData['title'], 
                $taskData['description'], 
                $taskData['assigned_by'], 
                $taskData['assigned_to'], 
                $taskData['task_type'],
                $taskData['priority'], 
                $taskData['deadline'],
                $taskData['planned_date'],
                $taskData['status'],
                $taskData['progress'],
                $taskData['sla_hours'],
                $taskData['department_id'],
                $taskData['task_category'],
                $taskData['project_id'],
                $followupRequired
            ]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                // Log task creation history
                $this->logTaskHistory($db, $taskId, 'created', '', 'Task created', 'Task was created with initial details');
                
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
                
                // Create followup if followup_required is checked
                if (!empty($_POST['followup_required'])) {
                    error_log('Creating followup for task ID: ' . $taskId . ' (followup_required checked)');
                    error_log('POST data for followup: ' . json_encode($_POST));
                    $this->createAutoFollowup($db, $taskId, $taskData, $_POST);
                } else {
                    error_log('No followup created - followup_required not checked. POST followup_required value: ' . ($_POST['followup_required'] ?? 'not set'));
                }
                
                error_log('Task created with ID: ' . $taskId . ', type: ' . $taskData['task_type'] . ', progress: ' . $taskData['progress'] . '%, planned_date: ' . ($taskData['planned_date'] ?? 'null'));
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
                'sla_hours' => floatval($_POST['sla_hours'] ?? 0.25),
                'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                'task_category' => trim($_POST['task_category'] ?? ''),
                'project_id' => !empty($_POST['project_id']) ? intval($_POST['project_id']) : null,
                'planned_date' => !empty($_POST['planned_date']) ? $_POST['planned_date'] : null,
                'followup_required' => !empty($_POST['followup_required']) ? 1 : 0
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
                
                // Get current task status before update for comparison
                $stmt = $db->prepare("SELECT status FROM tasks WHERE id = ?");
                $stmt->execute([$id]);
                $oldTask = $stmt->fetch(PDO::FETCH_ASSOC);
                $oldStatus = $oldTask ? $oldTask['status'] : null;
                
                $stmt = $db->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, task_type=?, priority=?, deadline=?, planned_date=?, status=?, progress=?, sla_hours=?, department_id=?, task_category=?, project_id=?, followup_required=?, updated_at=NOW() WHERE id=?");
                $result = $stmt->execute([
                    $taskData['title'], 
                    $taskData['description'], 
                    $taskData['assigned_to'], 
                    $taskData['task_type'],
                    $taskData['priority'], 
                    $taskData['deadline'], 
                    $taskData['planned_date'],
                    $taskData['status'],
                    $taskData['progress'],
                    $taskData['sla_hours'],
                    $taskData['department_id'],
                    $taskData['task_category'],
                    $taskData['project_id'],
                    $taskData['followup_required'],
                    $id
                ]);
                
                // Update follow-up if followup_required is checked
                if ($taskData['followup_required']) {
                    $this->updateTaskFollowup($db, $id, $_POST);
                }
                
                if ($result) {
                    // Log task update history
                    $this->logTaskHistory($db, $id, 'updated', 'Task details', 'Task updated', 'Task details were modified');
                    
                    // Update linked followups if status changed
                    if ($oldStatus && $oldStatus !== $taskData['status']) {
                        require_once __DIR__ . '/ContactFollowupController.php';
                        ContactFollowupController::updateLinkedFollowupStatus($id, $taskData['status']);
                    }
                    
                    error_log('Task updated with ID: ' . $id . ', progress: ' . $taskData['progress'] . '%, planned_date: ' . ($taskData['planned_date'] ?? 'null'));
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
            
            // Get task with department name and follow-up details
            $stmt = $db->prepare("SELECT t.*, d.name as department_name FROM tasks t LEFT JOIN departments d ON t.department_id = d.id WHERE t.id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get follow-up details if task has follow-up enabled
            if ($task && $task['followup_required']) {
                try {
                    $followupStmt = $db->prepare("SELECT * FROM followups WHERE task_id = ? ORDER BY created_at DESC LIMIT 1");
                    $followupStmt->execute([$id]);
                    $followup = $followupStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($followup) {
                        // Merge follow-up data into task array
                        $task['followup_type'] = $followup['followup_type'];
                        $task['followup_title'] = $followup['title'];
                        $task['followup_description'] = $followup['description'];
                        $task['follow_up_date'] = $followup['follow_up_date'];
                        $task['contact_id'] = $followup['contact_id'];
                        
                        // Get contact details if contact_id exists
                        if ($followup['contact_id']) {
                            $contactStmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
                            $contactStmt->execute([$followup['contact_id']]);
                            $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($contact) {
                                $task['contact_company'] = $contact['company'];
                                $task['contact_name'] = $contact['name'];
                                $task['contact_phone'] = $contact['phone'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('Follow-up fetch error: ' . $e->getMessage());
                }
            }
            
            if (!$task) {
                header('Location: /ergon/tasks?error=Task not found');
                exit;
            }
            
            $users = $this->getActiveUsers();
            $departments = $this->getDepartments();
            $projects = $this->getProjects();
            
            $data = [
                'task' => $task,
                'users' => $users,
                'departments' => $departments,
                'projects' => $projects,
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
    
    public function kanban() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get followups data including postponed/rescheduled
            $followups = [];
            try {
                $stmt = $db->prepare("SELECT f.*, u.name as assigned_user FROM followups f LEFT JOIN users u ON f.user_id = u.id WHERE f.user_id = ? AND f.status IN ('pending', 'in_progress', 'postponed', 'rescheduled') ORDER BY f.follow_up_date ASC");
                $stmt->execute([$_SESSION['user_id']]);
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Followups fetch error: " . $e->getMessage());
            }
            
            if (empty($tasks)) {
                $tasks = [];
            }
        } catch (Exception $e) {
            error_log("Kanban task fetch error: " . $e->getMessage());
            $tasks = [];
            $followups = [];
        }
        
        $data = ['tasks' => $tasks, 'followups' => $followups, 'active_page' => 'tasks'];
        $this->view('tasks/kanban', $data);
    }
    
    public function calendar() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? AND (t.deadline IS NOT NULL OR t.due_date IS NOT NULL) ORDER BY COALESCE(t.deadline, t.due_date) ASC");
            $stmt->execute([$_SESSION['user_id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tasks)) {
                $tasks = [];
            }
        } catch (Exception $e) {
            error_log("Calendar task fetch error: " . $e->getMessage());
            $tasks = [];
        }
        
        $data = ['tasks' => $tasks, 'active_page' => 'tasks'];
        $this->view('tasks/calendar', $data);
    }
    
    public function getTaskSchedule() {
        AuthMiddleware::requireAuth();
        
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $view = $_GET['view'] ?? 'calendar';
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            // Get tasks with date fields for visualization
            $stmt = $db->prepare("
                SELECT t.*, u.name as assigned_user, d.name as department_name, p.name as project_name
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.assigned_to = ? 
                AND (t.deadline IS NOT NULL OR t.created_at >= ?)
                ORDER BY COALESCE(t.deadline, t.created_at) ASC
            ");
            $stmt->execute([$_SESSION['user_id'], date('Y-m-01', mktime(0, 0, 0, $month, 1, $year))]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform tasks for visualization
            $visualizationData = [];
            foreach ($tasks as $task) {
                $taskDate = $task['deadline'] ? date('Y-m-d', strtotime($task['deadline'])) : date('Y-m-d', strtotime($task['created_at']));
                
                $visualizationData[] = [
                    'id' => $task['id'],
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'priority' => $task['priority'],
                    'status' => $task['status'],
                    'progress' => $task['progress'],
                    'assigned_user' => $task['assigned_user'],
                    'department_name' => $task['department_name'],
                    'project_name' => $task['project_name'],
                    'date' => $taskDate,
                    'type' => 'task'
                ];
            }
            
            $data = [
                'tasks' => $visualizationData,
                'current_month' => intval($month),
                'current_year' => intval($year),
                'view_type' => $view,
                'active_page' => 'tasks'
            ];
            
            $this->view('tasks/visualizer', $data);
        } catch (Exception $e) {
            error_log('Task schedule error: ' . $e->getMessage());
            $this->view('tasks/visualizer', [
                'tasks' => [],
                'current_month' => intval($month),
                'current_year' => intval($year),
                'view_type' => $view,
                'active_page' => 'tasks'
            ]);
        }
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
    
    public function viewDetails($id) {
        return $this->viewTask($id);
    }
    
    public function viewTask($id) {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTaskHistoryTable($db);
            
            // Get task with proper JOINs
            $stmt = $db->prepare("SELECT t.*, u.name as assigned_user, d.name as department_name, ub.name as assigned_by_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN departments d ON t.department_id = d.id LEFT JOIN users ub ON t.assigned_by = ub.id WHERE t.id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                header('Location: /ergon/tasks?error=not_found');
                exit;
            }
            
            // Get follow-ups for this task
            $followups = [];
            if ($task['followup_required']) {
                $stmt = $db->prepare("
                    SELECT f.*, c.name as contact_name, c.phone as contact_phone, c.email as contact_email, c.company as contact_company
                    FROM followups f 
                    LEFT JOIN contacts c ON f.contact_id = c.id 
                    WHERE f.task_id = ? 
                    ORDER BY f.follow_up_date DESC
                ");
                $stmt->execute([$id]);
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $data = [
                'task' => $task,
                'followups' => $followups,
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
        $status = $input['status'] ?? 'assigned';
        $progress = intval($input['progress'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit;
        }
        
        // Validate progress range
        if ($progress < 0 || $progress > 100) {
            echo json_encode(['success' => false, 'message' => 'Progress must be between 0 and 100']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            // Get current task data for history
            $stmt = $db->prepare("SELECT status, progress FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            
            $oldStatus = $currentData['status'];
            $oldProgress = $currentData['progress'];
            
            // Update both status and progress
            $stmt = $db->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $progress, $taskId]);
            
            if ($result) {
                // Log to task history if status or progress changed
                if ($oldStatus !== $status) {
                    $this->logTaskHistory($db, $taskId, 'status_changed', $oldStatus, $status, $reason);
                }
                if ($oldProgress != $progress) {
                    $this->logTaskHistory($db, $taskId, 'progress_updated', $oldProgress . '%', $progress . '%', $reason);
                }
                
                // Update linked followups if task status changed
                if ($oldStatus !== $status) {
                    require_once __DIR__ . '/ContactFollowupController.php';
                    ContactFollowupController::updateLinkedFollowupStatus($taskId, $status);
                }
                
                echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update task']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function getTaskHistory($id) {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTaskHistoryTable($db);
            
            $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM task_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.task_id = ? ORDER BY h.created_at DESC");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = empty($history) ? '<p>No history available for this task.</p>' : $this->renderTaskHistory($history);
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            error_log('Task history error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    private function renderTaskHistory($history) {
        if (empty($history)) {
            return '<div class="no-history"><p>📝 No history available for this task.</p></div>';
        }
        
        $html = '<div class="history-timeline">';
        foreach ($history as $entry) {
            $actionIcon = $this->getActionIcon($entry['action']);
            $actionColor = $this->getActionColor($entry['action']);
            
            $html .= '<div class="history-entry" style="border-left-color: ' . $actionColor . ';">';
            $html .= '<div class="history-icon" style="background-color: ' . $actionColor . ';">' . $actionIcon . '</div>';
            $html .= '<div class="history-content">';
            $html .= '<div class="history-header">';
            $html .= '<span class="history-action">' . $this->formatActionText($entry['action']) . '</span>';
            $html .= '<span class="history-time">' . $this->formatTimeAgo($entry['created_at']) . '</span>';
            $html .= '</div>';
            
            if ($entry['old_value'] && $entry['new_value']) {
                $html .= '<div class="history-change">';
                $html .= '<span class="change-from">From: ' . htmlspecialchars($entry['old_value']) . '</span>';
                $html .= '<span class="change-arrow">→</span>';
                $html .= '<span class="change-to">To: ' . htmlspecialchars($entry['new_value']) . '</span>';
                $html .= '</div>';
            }
            
            if ($entry['notes']) {
                $html .= '<div class="history-notes">💬 ' . htmlspecialchars($entry['notes']) . '</div>';
            }
            
            $html .= '<div class="history-user">👤 ' . htmlspecialchars($entry['user_name'] ?? 'System') . '</div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function getActionIcon($action) {
        return match($action) {
            'created' => '✨',
            'status_changed' => '🔄',
            'progress_updated' => '📊',
            'assigned' => '👤',
            'completed' => '✅',
            'cancelled' => '❌',
            'updated' => '✏️',
            'commented' => '💬',
            default => '📝'
        };
    }
    
    private function getActionColor($action) {
        return match($action) {
            'created' => '#10b981',
            'status_changed' => '#3b82f6',
            'progress_updated' => '#8b5cf6',
            'assigned' => '#f59e0b',
            'completed' => '#059669',
            'cancelled' => '#ef4444',
            'updated' => '#6b7280',
            'commented' => '#06b6d4',
            default => '#9ca3af'
        };
    }
    
    private function formatActionText($action) {
        return match($action) {
            'created' => 'Task Created',
            'status_changed' => 'Status Changed',
            'progress_updated' => 'Progress Updated',
            'assigned' => 'Task Assigned',
            'completed' => 'Task Completed',
            'cancelled' => 'Task Cancelled',
            'updated' => 'Task Updated',
            'commented' => 'Comment Added',
            default => ucfirst(str_replace('_', ' ', $action))
        };
    }
    
    private function formatTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Just now';
        if ($time < 3600) return floor($time/60) . 'm ago';
        if ($time < 86400) return floor($time/3600) . 'h ago';
        if ($time < 2592000) return floor($time/86400) . 'd ago';
        
        return date('M d, Y', strtotime($datetime));
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
    
    private function getProjects() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name, status FROM projects WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $projects;
        } catch (Exception $e) {
            error_log('Error fetching projects: ' . $e->getMessage());
            return [];
        }
    }
    
    private function createAutoFollowup($db, $taskId, $taskData, $postData) {
        try {
            // Ensure followups table exists
            $this->ensureFollowupsTable($db);
            
            // Use follow-up specific data if provided, otherwise use task data
            $followupDate = !empty($postData['follow_up_date']) ? $postData['follow_up_date'] : 
                          (!empty($taskData['deadline']) ? date('Y-m-d', strtotime($taskData['deadline'])) : date('Y-m-d', strtotime('+1 day')));
            
            $followupTime = '09:00:00'; // Default time
            
            // Use custom title if provided, otherwise generate one
            $followupTitle = !empty($postData['followup_title']) ? $postData['followup_title'] : 'Follow-up: ' . $taskData['title'];
            if (!empty($postData['contact_company']) && empty($postData['followup_title'])) {
                $followupTitle = 'Follow-up: ' . $postData['contact_company'] . ' - ' . $taskData['title'];
            }
            
            // Use custom description if provided, otherwise generate one
            $followupDesc = !empty($postData['followup_description']) ? $postData['followup_description'] : 'Auto-created follow-up for task: ' . $taskData['title'];
            if (!empty($taskData['description']) && empty($postData['followup_description'])) {
                $followupDesc .= "\n\nTask Description: " . $taskData['description'];
            }
            
            // Create followup record in followups table with correct structure
            $stmt = $db->prepare("
                INSERT INTO followups (
                    title, description, followup_type, task_id, contact_id, user_id,
                    follow_up_date, status, created_at, updated_at
                ) VALUES (?, ?, 'task', ?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            
            $contactId = !empty($postData['contact_id']) ? intval($postData['contact_id']) : null;
            // Assign follow-up to the same user as the task
            $assignedUserId = $taskData['assigned_to'];
            
            $result = $stmt->execute([
                $followupTitle,
                $followupDesc,
                $taskId,
                $contactId,
                $assignedUserId,
                $followupDate
            ]);
            
            error_log('Follow-up creation attempt - Title: ' . $followupTitle . ', Task ID: ' . $taskId);
            error_log('Follow-up SQL parameters: ' . json_encode([$followupTitle, $followupDesc, $taskId, $contactId, $followupDate]));
            
            if ($result) {
                $followupId = $db->lastInsertId();
                error_log('SUCCESS: Follow-up created with ID: ' . $followupId . ' for task ID: ' . $taskId);
                
                // Verify the record was actually inserted
                $verifyStmt = $db->prepare("SELECT * FROM followups WHERE id = ?");
                $verifyStmt->execute([$followupId]);
                $insertedRecord = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                error_log('Inserted followup record: ' . json_encode($insertedRecord));
            } else {
                error_log('FAILED to create follow-up. SQL Error: ' . implode(', ', $stmt->errorInfo()));
                error_log('SQL Query: INSERT INTO followups (title, description, followup_type, task_id, contact_id, follow_up_date, status, created_at, updated_at) VALUES (?, ?, "task", ?, ?, ?, "pending", NOW(), NOW())');
            }
        } catch (Exception $e) {
            error_log('Auto-followup creation failed: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    private function updateTaskFollowup($db, $taskId, $postData) {
        try {
            $this->ensureFollowupsTable($db);
            
            // Check if follow-up already exists for this task
            $stmt = $db->prepare("SELECT id FROM followups WHERE task_id = ?");
            $stmt->execute([$taskId]);
            $existingFollowup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $followupTitle = !empty($postData['followup_title']) ? $postData['followup_title'] : 'Follow-up: ' . ($postData['title'] ?? 'Task');
            $followupDesc = !empty($postData['followup_description']) ? $postData['followup_description'] : 'Follow-up for task';
            $followupDate = !empty($postData['follow_up_date']) ? $postData['follow_up_date'] : date('Y-m-d', strtotime('+1 day'));
            $contactId = !empty($postData['contact_id']) ? intval($postData['contact_id']) : null;
            $followupType = $postData['followup_type'] ?? 'task';
            
            // Get task assigned user for follow-up assignment
            $taskStmt = $db->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
            $taskStmt->execute([$taskId]);
            $taskInfo = $taskStmt->fetch(PDO::FETCH_ASSOC);
            $assignedUserId = $taskInfo ? $taskInfo['assigned_to'] : $_SESSION['user_id'];
            
            if ($existingFollowup) {
                // Update existing follow-up
                $stmt = $db->prepare("UPDATE followups SET title=?, description=?, followup_type=?, contact_id=?, user_id=?, follow_up_date=?, updated_at=NOW() WHERE task_id=?");
                $stmt->execute([$followupTitle, $followupDesc, $followupType, $contactId, $assignedUserId, $followupDate, $taskId]);
            } else {
                // Create new follow-up
                $stmt = $db->prepare("INSERT INTO followups (title, description, followup_type, task_id, contact_id, user_id, follow_up_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
                $stmt->execute([$followupTitle, $followupDesc, $followupType, $taskId, $contactId, $assignedUserId, $followupDate]);
            }
            
            // Update or create contact if manual entry provided
            if (!empty($postData['contact_company']) || !empty($postData['contact_name'])) {
                $this->updateOrCreateContact($db, $postData, $taskId);
            }
        } catch (Exception $e) {
            error_log('Update task follow-up error: ' . $e->getMessage());
        }
    }
    
    private function updateOrCreateContact($db, $postData, $taskId) {
        try {
            $contactName = trim($postData['contact_name'] ?? '');
            $contactCompany = trim($postData['contact_company'] ?? '');
            $contactPhone = trim($postData['contact_phone'] ?? '');
            
            if ($contactName || $contactCompany) {
                // Check if contact exists
                $stmt = $db->prepare("SELECT id FROM contacts WHERE name = ? OR company = ?");
                $stmt->execute([$contactName, $contactCompany]);
                $existingContact = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingContact) {
                    // Update existing contact
                    $stmt = $db->prepare("UPDATE contacts SET name=?, company=?, phone=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$contactName, $contactCompany, $contactPhone, $existingContact['id']]);
                    $contactId = $existingContact['id'];
                } else {
                    // Create new contact
                    $stmt = $db->prepare("INSERT INTO contacts (name, company, phone, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$contactName, $contactCompany, $contactPhone]);
                    $contactId = $db->lastInsertId();
                }
                
                // Update follow-up with contact_id
                $stmt = $db->prepare("UPDATE followups SET contact_id=? WHERE task_id=?");
                $stmt->execute([$contactId, $taskId]);
            }
        } catch (Exception $e) {
            error_log('Update or create contact error: ' . $e->getMessage());
        }
    }
    
    private function ensureFollowupsTable($db) {
        try {
            // Create followups table with exact structure matching followup module
            $db->exec("CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                followup_type ENUM('standalone','task') DEFAULT 'standalone',
                task_id INT NULL,
                contact_id INT NULL,
                follow_up_date DATE NOT NULL,
                status ENUM('pending','in_progress','completed','postponed','cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_task_id (task_id),
                INDEX idx_contact_id (contact_id),
                INDEX idx_follow_date (follow_up_date),
                INDEX idx_status (status)
            )");
            
            // Ensure contacts table exists
            $db->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Add missing columns if they don't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                
                if (!in_array('followup_type', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN followup_type ENUM('standalone','task') DEFAULT 'standalone' AFTER description");
                }
                
                if (!in_array('task_id', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER followup_type");
                    $db->exec("ALTER TABLE followups ADD INDEX idx_task_id (task_id)");
                }
                
                if (!in_array('contact_id', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN contact_id INT NULL AFTER task_id");
                    $db->exec("ALTER TABLE followups ADD INDEX idx_contact_id (contact_id)");
                }
            } catch (Exception $e) {
                error_log('Column addition error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log('ensureFollowupsTable error: ' . $e->getMessage());
        }
    }
    
    private function logTaskHistory($db, $taskId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            $this->ensureTaskHistoryTable($db);
            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$taskId, $action, $oldValue, $newValue, $notes, $_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log('Task history log error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function ensureTaskHistoryTable($db) {
        try {
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
            
            // Check if we need to populate initial history for existing tasks
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM task_history");
            $stmt->execute();
            $historyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($historyCount == 0) {
                // Create initial history entries for existing tasks
                $stmt = $db->prepare("SELECT id, title, status, progress, assigned_to, created_at FROM tasks ORDER BY created_at DESC LIMIT 50");
                $stmt->execute();
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $insertStmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($tasks as $task) {
                    // Add creation history
                    $insertStmt->execute([
                        $task['id'],
                        'created',
                        '',
                        'Task created',
                        'Initial task creation: ' . $task['title'],
                        $task['assigned_to'] ?? 1,
                        $task['created_at']
                    ]);
                    
                    // Add status history if not default
                    if ($task['status'] !== 'assigned') {
                        $insertStmt->execute([
                            $task['id'],
                            'status_changed',
                            'assigned',
                            $task['status'],
                            'Status updated to ' . $task['status'],
                            $task['assigned_to'] ?? 1,
                            date('Y-m-d H:i:s', strtotime($task['created_at'] . ' +1 hour'))
                        ]);
                    }
                    
                    // Add progress history if not 0
                    if ($task['progress'] > 0) {
                        $insertStmt->execute([
                            $task['id'],
                            'progress_updated',
                            '0%',
                            $task['progress'] . '%',
                            'Progress updated to ' . $task['progress'] . '%',
                            $task['assigned_to'] ?? 1,
                            date('Y-m-d H:i:s', strtotime($task['created_at'] . ' +2 hours'))
                        ]);
                    }
                }
                
                error_log('Created initial task history entries for ' . count($tasks) . ' tasks');
            }
        } catch (Exception $e) {
            error_log('ensureTaskHistoryTable error: ' . $e->getMessage());
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
                status ENUM('assigned','in_progress','completed','cancelled','suspended') DEFAULT 'assigned',
                due_date DATE DEFAULT NULL,
                depends_on_task_id INT DEFAULT NULL,
                sla_hours DECIMAL(8,4) DEFAULT 0.25,
                department_id INT DEFAULT NULL,
                task_category VARCHAR(100) DEFAULT NULL,
                project_id INT DEFAULT NULL,
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
            
            // Check if project_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'project_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE tasks ADD COLUMN project_id INT DEFAULT NULL");
                error_log('Added project_id column to tasks table');
            }
            
            // Check if followup_required column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'followup_required'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE tasks ADD COLUMN followup_required TINYINT(1) DEFAULT 0");
                error_log('Added followup_required column to tasks table');
            }
            
            // Check if planned_date column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL");
                error_log('Added planned_date column to tasks table');
            }
            
            // Update sla_hours column to DECIMAL if it's still INT
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'sla_hours'");
            $stmt->execute();
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && strpos(strtolower($column['Type']), 'int') !== false) {
                $db->exec("ALTER TABLE tasks MODIFY COLUMN sla_hours DECIMAL(8,4) DEFAULT 0.25");
                error_log('Updated sla_hours column to DECIMAL type');
            }
        } catch (Exception $e) {
            error_log('ensureTasksTable error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fallback method for static tasks when database is unavailable
     */
    private function getStaticTasks() {
        return [
            [
                'id' => 1,
                'title' => 'Sample Task',
                'description' => 'This is a sample task for demonstration',
                'status' => 'assigned',
                'priority' => 'medium',
                'progress' => 0,
                'assigned_user' => 'System',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
}
?>
