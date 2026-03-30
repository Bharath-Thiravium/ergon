<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/ModuleMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';

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
        ModuleMiddleware::requireModule('tasks');
        
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
        ModuleMiddleware::requireModule('tasks');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        $users = $this->getActiveUsers();
        $departments = $this->getDepartments();
        $projects = $this->getProjects();
        $contacts = $this->getContacts();
        
        $data = [
            'users' => $users,
            'departments' => $departments,
            'projects' => $projects,
            'contacts' => $contacts,
            'active_page' => 'tasks'
        ];
        $this->view('tasks/create', $data);
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTasksTable($db);
            
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE status = 'active' AND role IN ('admin', 'user') ORDER BY name");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                // Fallback to current user if no users found
                return [['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name'] ?? 'Current User', 'email' => '']];
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
            'sla_hours' => max(0.01, floatval($_POST['sla_hours'] ?? 0.25)),
            'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'task_category' => trim($_POST['task_category'] ?? ''),
            'project_id' => !empty($_POST['project_id']) ? intval($_POST['project_id']) : null,
            'subcategory_id' => !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null
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
            
            $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, planned_date, status, progress, sla_hours, department_id, task_category, project_id, subcategory_id, followup_required, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
                $taskData['subcategory_id'],
                $followupRequired
            ]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                // Get assigned user details for comprehensive logging
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$taskData['assigned_to']]);
                $assignedUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$taskData['assigned_by']]);
                $assignedByUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Log comprehensive task creation history
                $creationNotes = sprintf(
                    'Task "%s" created | Assigned to: %s | Priority: %s | Type: %s | SLA: %.2fh%s%s%s',
                    $taskData['title'],
                    $assignedUser['name'] ?? 'Unknown',
                    ucfirst($taskData['priority']),
                    ucfirst(str_replace('-', ' ', $taskData['task_type'])),
                    $taskData['sla_hours'],
                    $taskData['deadline'] ? ' | Deadline: ' . date('M d, Y H:i', strtotime($taskData['deadline'])) : '',
                    $taskData['planned_date'] ? ' | Planned: ' . date('M d, Y', strtotime($taskData['planned_date'])) : '',
                    $taskData['description'] ? ' | Description: ' . substr($taskData['description'], 0, 100) . (strlen($taskData['description']) > 100 ? '...' : '') : ''
                );
                $this->logTaskHistory($db, $taskId, 'created', '', 'Task created', $creationNotes);
                
                // Log assignment if different from creator
                if ($taskData['assigned_by'] != $taskData['assigned_to']) {
                    $assignmentNotes = sprintf(
                        'Task assigned by %s to %s',
                        $assignedByUser['name'] ?? 'System',
                        $assignedUser['name'] ?? 'Unknown'
                    );
                    $this->logTaskHistory($db, $taskId, 'assigned', $assignedByUser['name'] ?? 'System', $assignedUser['name'] ?? 'Unknown', $assignmentNotes);
                }
                
                // Create notifications for task assignment
                require_once __DIR__ . '/../helpers/NotificationHelper.php';
                
                if ($assignedUser && $taskData['assigned_by'] != $taskData['assigned_to']) {
                    // Notify assigned user (only if not self-assignment)
                    NotificationHelper::notifyUser(
                        $taskData['assigned_by'],
                        $taskData['assigned_to'],
                        'tasks',
                        'assigned',
                        "You have been assigned a new task: {$taskData['title']}",
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
        
        // Check permissions before allowing edit
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT assigned_to, assigned_by FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                header('Location: /ergon/tasks?error=Task not found');
                exit;
            }
            
            // Check permissions for editing
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentUserRole = $_SESSION['role'] ?? 'user';
            $isAssignedUser = ($task['assigned_to'] ?? 0) == $currentUserId;
            $isTaskCreator = ($task['assigned_by'] ?? 0) == $currentUserId;
            $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
            
            if (!$isAssignedUser && !$isTaskCreator && !$isAdmin) {
                header('Location: /ergon/tasks?error=Permission denied: You can only edit tasks you are involved with');
                exit;
            }
        } catch (Exception $e) {
            error_log('Edit permission check error: ' . $e->getMessage());
            header('Location: /ergon/tasks?error=Permission check failed');
            exit;
        }
        
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
                'sla_hours' => max(0.01, floatval($_POST['sla_hours'] ?? 0.25)),
                'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                'task_category' => trim($_POST['task_category'] ?? ''),
                'project_id' => !empty($_POST['project_id']) ? intval($_POST['project_id']) : null,
                'planned_date' => !empty($_POST['planned_date']) ? $_POST['planned_date'] : null,
                'followup_required' => !empty($_POST['followup_required']) ? 1 : 0,
                'subcategory_id' => !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null
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
                
                // Get complete current task data before update for comparison
                $stmt = $db->prepare("SELECT t.*, u1.name as assigned_to_name, u2.name as assigned_by_name FROM tasks t LEFT JOIN users u1 ON t.assigned_to = u1.id LEFT JOIN users u2 ON t.assigned_by = u2.id WHERE t.id = ?");
                $stmt->execute([$id]);
                $oldTaskFull = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $db->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, task_type=?, priority=?, deadline=?, planned_date=?, status=?, progress=?, sla_hours=?, department_id=?, task_category=?, project_id=?, subcategory_id=?, followup_required=?, updated_at=NOW() WHERE id=?");
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
                    $taskData['subcategory_id'],
                    $taskData['followup_required'],
                    $id
                ]);
                
                // Update follow-up if followup_required is checked
                if ($taskData['followup_required']) {
                    $this->updateTaskFollowup($db, $id, $_POST);
                }
                
                if ($result) {
                    // Get new user name for comparison
                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->execute([$taskData['assigned_to']]);
                    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $changes = [];
                    
                    // Compare all fields and log changes
                    if (($oldTaskFull['title'] ?? '') !== $taskData['title']) {
                        $changes[] = 'Title';
                        $this->logTaskHistory($db, $id, 'title_changed', $oldTaskFull['title'] ?? '', $taskData['title'], sprintf('Title changed from "%s" to "%s"', $oldTaskFull['title'] ?? '', $taskData['title']));
                    }
                    
                    if (($oldTaskFull['description'] ?? '') !== ($taskData['description'] ?? '')) {
                        $changes[] = 'Description';
                        $this->logTaskHistory($db, $id, 'description_changed', 'Description updated', 'Description updated', 'Task description was modified');
                    }
                    
                    if (($oldTaskFull['status'] ?? '') !== $taskData['status']) {
                        $changes[] = 'Status';
                        $this->logTaskHistory($db, $id, 'status_changed', ucfirst($oldTaskFull['status'] ?? ''), ucfirst($taskData['status']), sprintf('Status changed from "%s" to "%s"', ucfirst($oldTaskFull['status'] ?? ''), ucfirst($taskData['status'])));
                    }
                    
                    if (($oldTaskFull['assigned_to'] ?? 0) !== $taskData['assigned_to']) {
                        $changes[] = 'Assignment';
                        $this->logTaskHistory($db, $id, 'reassigned', $oldTaskFull['assigned_to_name'] ?? 'Unknown', $newUser['name'] ?? 'Unknown', sprintf('Task reassigned from "%s" to "%s"', $oldTaskFull['assigned_to_name'] ?? 'Unknown', $newUser['name'] ?? 'Unknown'));
                    }
                    
                    if (($oldTaskFull['priority'] ?? 'medium') !== $taskData['priority']) {
                        $changes[] = 'Priority';
                        $this->logTaskHistory($db, $id, 'priority_changed', ucfirst($oldTaskFull['priority'] ?? 'medium'), ucfirst($taskData['priority']), sprintf('Priority changed from "%s" to "%s"', ucfirst($oldTaskFull['priority'] ?? 'medium'), ucfirst($taskData['priority'])));
                    }
                    
                    if (($oldTaskFull['deadline'] ?? '') !== ($taskData['deadline'] ?? '')) {
                        $changes[] = 'Deadline';
                        $oldDeadline = $oldTaskFull['deadline'] ? date('M d, Y H:i', strtotime($oldTaskFull['deadline'])) : 'None';
                        $newDeadline = $taskData['deadline'] ? date('M d, Y H:i', strtotime($taskData['deadline'])) : 'None';
                        $this->logTaskHistory($db, $id, 'deadline_changed', $oldDeadline, $newDeadline, sprintf('Deadline changed from "%s" to "%s"', $oldDeadline, $newDeadline));
                    }
                    
                    if (($oldTaskFull['task_type'] ?? 'ad-hoc') !== $taskData['task_type']) {
                        $changes[] = 'Type';
                        $this->logTaskHistory($db, $id, 'type_changed', ucfirst($oldTaskFull['task_type'] ?? 'ad-hoc'), ucfirst($taskData['task_type']), sprintf('Task type changed from "%s" to "%s"', ucfirst($oldTaskFull['task_type'] ?? 'ad-hoc'), ucfirst($taskData['task_type'])));
                    }
                    
                    if (($oldTaskFull['sla_hours'] ?? 0.25) != $taskData['sla_hours']) {
                        $changes[] = 'SLA';
                        $this->logTaskHistory($db, $id, 'sla_changed', $oldTaskFull['sla_hours'] ?? 0.25, $taskData['sla_hours'], sprintf('SLA changed from "%s hours" to "%s hours"', $oldTaskFull['sla_hours'] ?? 0.25, $taskData['sla_hours']));
                    }
                    
                    if (($oldTaskFull['progress'] ?? 0) !== $taskData['progress']) {
                        $changes[] = 'Progress';
                        $this->logTaskHistory($db, $id, 'progress_changed', ($oldTaskFull['progress'] ?? 0) . '%', $taskData['progress'] . '%', sprintf('Progress changed from "%d%%" to "%d%%"', $oldTaskFull['progress'] ?? 0, $taskData['progress']));
                    }
                    
                    // Log summary if changes were made
                    if (!empty($changes)) {
                        $this->logTaskHistory($db, $id, 'updated', 'Task details', 'Task updated', 'Updated: ' . implode(', ', $changes));
                    }
                    
                    // Update linked followups if status changed
                    if (($oldTaskFull['status'] ?? '') !== $taskData['status']) {
                        require_once __DIR__ . '/ContactFollowupController.php';
                        ContactFollowupController::updateLinkedFollowupStatus($id, $taskData['status']);
                    }
                    
                    // Sync with planner (daily_tasks table)
                    $taskData['old_assigned_to'] = $oldTaskFull['assigned_to'] ?? null;
                    $this->syncWithPlanner($db, $id, $taskData);
                    
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
            
            // Get tasks based on user role
            if (($_SESSION['role'] ?? 'user') === 'user') {
                // Regular users see only their assigned tasks
                $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC");
                $stmt->execute([$_SESSION['user_id']]);
            } else {
                // Admins/owners see all tasks
                $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC");
                $stmt->execute();
            }
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get followups data including postponed/rescheduled
            $followups = [];
            try {
                $stmt = $db->prepare("SELECT f.*, u.name as assigned_user FROM followups f LEFT JOIN users u ON f.user_id = u.id LEFT JOIN tasks t ON f.task_id = t.id WHERE f.user_id = ? AND f.status IN ('pending', 'in_progress', 'postponed', 'rescheduled') AND (f.task_id IS NULL OR t.id IS NOT NULL) ORDER BY f.follow_up_date ASC");
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
            
            $role = $_SESSION['role'] ?? 'user';
            if (in_array($role, ['admin', 'owner', 'company_owner'])) {
                $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.status NOT IN ('completed','cancelled') ORDER BY COALESCE(t.planned_date, t.deadline, t.created_at) ASC");
                $stmt->execute();
            } else {
                $stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? AND t.status NOT IN ('completed','cancelled') ORDER BY COALESCE(t.planned_date, t.deadline, t.created_at) ASC");
                $stmt->execute([$_SESSION['user_id']]);
            }
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            
            // Get tasks with date fields for visualization based on user role
            if (($_SESSION['role'] ?? 'user') === 'user') {
                $stmt = $db->prepare("
                    SELECT t.*, u.name as assigned_user, d.name as department_name, p.name as project_name
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN departments d ON t.department_id = d.id
                    LEFT JOIN projects p ON t.project_id = p.id
                    WHERE t.assigned_to = ? 
                    AND (t.deadline IS NOT NULL OR t.planned_date IS NOT NULL OR t.created_at >= ?)
                    ORDER BY COALESCE(t.deadline, t.planned_date, t.created_at) ASC
                ");
                $stmt->execute([$_SESSION['user_id'], date('Y-m-01', mktime(0, 0, 0, $month, 1, $year))]);
            } else {
                $stmt = $db->prepare("
                    SELECT t.*, u.name as assigned_user, d.name as department_name, p.name as project_name
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN departments d ON t.department_id = d.id
                    LEFT JOIN projects p ON t.project_id = p.id
                    WHERE (t.deadline IS NOT NULL OR t.planned_date IS NOT NULL OR t.created_at >= ?)
                    ORDER BY COALESCE(t.deadline, t.planned_date, t.created_at) ASC
                ");
                $stmt->execute([date('Y-m-01', mktime(0, 0, 0, $month, 1, $year))]);
            }
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
        $progress = intval($input['progress'] ?? 0);
        $description = trim($input['description'] ?? '');
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit;
        }
        
        // Validate progress range
        if ($progress < 0 || $progress > 100) {
            echo json_encode(['success' => false, 'message' => 'Progress must be between 0 and 100']);
            exit;
        }
        
        // Description is required for progress updates
        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Progress description is required']);
            exit;
        }
        
        try {
            $this->ensureProgressHistoryTable();
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get current task data and check permissions
            $stmt = $db->prepare("SELECT progress, status, title, assigned_to, assigned_by FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentTask) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }
            
            // Check permissions for progress updates
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentUserRole = $_SESSION['role'] ?? 'user';
            $isAssignedUser = ($currentTask['assigned_to'] ?? 0) == $currentUserId;
            $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
            
            if (!$isAssignedUser && !$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: You can only update progress for tasks assigned to you']);
                exit;
            }
            
            $result = $this->taskModel->updateProgress($taskId, $_SESSION['user_id'], $progress, $description);
            
            if ($result) {
                // Sync with daily_tasks table if exists
                try {
                    $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'assigned');
                    $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, completed_percentage = ?, updated_at = NOW() WHERE original_task_id = ? OR task_id = ?");
                    $stmt->execute([$status, $progress, $taskId, $taskId]);
                } catch (Exception $e) {
                    error_log('Daily tasks sync error: ' . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Progress updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function getProgressHistory($id) {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get task data and check permissions
            $stmt = $db->prepare("SELECT assigned_to, assigned_by FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
                exit;
            }
            
            // Check permissions for viewing history
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentUserRole = $_SESSION['role'] ?? 'user';
            $isAssignedUser = ($task['assigned_to'] ?? 0) == $currentUserId;
            $isTaskCreator = ($task['assigned_by'] ?? 0) == $currentUserId;
            $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
            
            if (!$isAssignedUser && !$isTaskCreator && !$isAdmin) {
                echo json_encode(['success' => false, 'error' => 'Permission denied: You can only view history for tasks you are involved with']);
                exit;
            }
            
            $history = $this->taskModel->getProgressHistory($id);
            
            $html = empty($history) ? '<p>No progress history available.</p>' : $this->renderProgressHistory($history);
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            error_log('Progress history error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    private function renderProgressHistory($history) {
        if (empty($history)) {
            return '<div class="no-history"><p>📊 No progress history available.</p></div>';
        }
        
        $html = '<div class="progress-timeline">';
        foreach ($history as $entry) {
            $progressChange = $entry['progress_to'] - $entry['progress_from'];
            $changeIcon = $progressChange > 0 ? '📈' : ($progressChange < 0 ? '📉' : '📊');
            $changeColor = $progressChange > 0 ? '#10b981' : ($progressChange < 0 ? '#ef4444' : '#6b7280');
            
            $html .= '<div class="progress-entry" style="border-left-color: ' . $changeColor . ';">';
            $html .= '<div class="progress-icon" style="background-color: ' . $changeColor . ';">' . $changeIcon . '</div>';
            $html .= '<div class="progress-content">';
            $html .= '<div class="progress-header">';
            $html .= '<span class="progress-change">Progress: ' . $entry['progress_from'] . '% → ' . $entry['progress_to'] . '%</span>';
            $html .= '<span class="progress-time">' . $this->formatTimeAgo($entry['created_at']) . '</span>';
            $html .= '</div>';
            
            if ($entry['status_from'] !== $entry['status_to']) {
                $html .= '<div class="status-change">';
                $html .= '<span class="status-from">Status: ' . htmlspecialchars($entry['status_from']) . '</span>';
                $html .= '<span class="status-arrow">→</span>';
                $html .= '<span class="status-to">' . htmlspecialchars($entry['status_to']) . '</span>';
                $html .= '</div>';
            }
            
            if ($entry['description']) {
                $html .= '<div class="progress-description">💬 ' . htmlspecialchars($entry['description']) . '</div>';
            }
            
            $html .= '<div class="progress-user">👤 ' . htmlspecialchars($entry['user_name'] ?? 'System') . '</div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    public function getTaskHistory($id) {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTaskHistoryTable($db);
            
            // Get task data and check permissions
            $stmt = $db->prepare("SELECT assigned_to, assigned_by FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
                exit;
            }
            
            // Check permissions for viewing task history
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentUserRole = $_SESSION['role'] ?? 'user';
            $isAssignedUser = ($task['assigned_to'] ?? 0) == $currentUserId;
            $isTaskCreator = ($task['assigned_by'] ?? 0) == $currentUserId;
            $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
            
            if (!$isAssignedUser && !$isTaskCreator && !$isAdmin) {
                echo json_encode(['success' => false, 'error' => 'Permission denied: You can only view history for tasks you are involved with']);
                exit;
            }
            
            // Get comprehensive task history including progress updates
            $stmt = $db->prepare("
                SELECT 
                    'history' as source_type,
                    h.id,
                    h.task_id,
                    h.action,
                    h.old_value,
                    h.new_value,
                    h.notes,
                    h.created_by,
                    h.created_at,
                    u.name as user_name,
                    NULL as progress_from,
                    NULL as progress_to,
                    NULL as description
                FROM task_history h 
                LEFT JOIN users u ON h.created_by = u.id 
                WHERE h.task_id = ?
                
                UNION ALL
                
                SELECT 
                    'progress' as source_type,
                    p.id,
                    p.task_id,
                    'progress_updated' as action,
                    CONCAT(p.progress_from, '%') as old_value,
                    CONCAT(p.progress_to, '%') as new_value,
                    p.description as notes,
                    p.user_id as created_by,
                    p.created_at,
                    u.name as user_name,
                    p.progress_from,
                    p.progress_to,
                    p.description
                FROM task_progress_history p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.task_id = ?
                
                ORDER BY created_at DESC
            ");
            $stmt->execute([$id, $id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = empty($history) ? '<p>No history available for this task.</p>' : $this->renderEnhancedTaskHistory($history);
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            error_log('Task history error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    private function renderEnhancedTaskHistory($history) {
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
            
            // Enhanced display for different entry types
            if ($entry['source_type'] === 'progress') {
                // Progress update entry - show detailed progress information
                $progressChange = $entry['progress_to'] - $entry['progress_from'];
                $changeDirection = $progressChange > 0 ? '↗️' : ($progressChange < 0 ? '↘️' : '➡️');
                
                $html .= '<div class="history-change progress-change">';
                $html .= '<div class="progress-details">';
                $html .= '<span class="change-label">Progress Update:</span>';
                $html .= '<span class="progress-from">' . $entry['progress_from'] . '%</span>';
                $html .= '<span class="change-arrow">' . $changeDirection . '</span>';
                $html .= '<span class="progress-to">' . $entry['progress_to'] . '%</span>';
                $html .= '<span class="progress-delta">(' . ($progressChange > 0 ? '+' : '') . $progressChange . '%)</span>';
                $html .= '</div>';
                $html .= '</div>';
                
                if ($entry['description']) {
                    $html .= '<div class="history-notes progress-notes">💬 ' . htmlspecialchars($entry['description']) . '</div>';
                }
            } else {
                // Regular history entry - show old/new values if available
                if ($entry['old_value'] && $entry['new_value'] && $entry['old_value'] !== $entry['new_value']) {
                    $html .= '<div class="history-change">';
                    $html .= '<span class="change-from">From: ' . htmlspecialchars($entry['old_value']) . '</span>';
                    $html .= '<span class="change-arrow">→</span>';
                    $html .= '<span class="change-to">To: ' . htmlspecialchars($entry['new_value']) . '</span>';
                    $html .= '</div>';
                } elseif ($entry['new_value'] && !$entry['old_value']) {
                    $html .= '<div class="history-change">';
                    $html .= '<span class="change-to">Set to: ' . htmlspecialchars($entry['new_value']) . '</span>';
                    $html .= '</div>';
                }
                
                if ($entry['notes']) {
                    $html .= '<div class="history-notes">💬 ' . htmlspecialchars($entry['notes']) . '</div>';
                }
            }
            
            $html .= '<div class="history-user">👤 ' . htmlspecialchars($entry['user_name'] ?? 'System') . '</div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        
        // Add enhanced CSS for better styling
        $html .= '<style>
        .progress-change {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
        }
        .progress-details {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .change-label {
            font-weight: 600;
            color: #0369a1;
            font-size: 0.9rem;
        }
        .progress-from {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .progress-to {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .progress-delta {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .progress-notes {
            background: #f8fafc;
            border-left: 4px solid #0ea5e9;
            padding: 8px 12px;
            margin: 8px 0;
            border-radius: 0 6px 6px 0;
            font-style: italic;
        }
        .history-change {
            margin: 6px 0;
            padding: 8px;
            background: var(--bg-tertiary, #f8fafc);
            border-radius: 6px;
            border-left: 3px solid var(--primary, #3b82f6);
        }
        .change-from {
            background: #fef2f2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: line-through;
            opacity: 0.8;
        }
        .change-to {
            background: #f0fdf4;
            color: #166534;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
        .change-arrow {
            color: var(--primary, #3b82f6);
            font-weight: bold;
            margin: 0 4px;
        }
        </style>';
        
        return $html;
    }
    
    private function renderTaskHistory($history) {
        // Keep the old method for backward compatibility
        return $this->renderEnhancedTaskHistory($history);
    }
    
    private function getActionIcon($action) {
        return match($action) {
            'created' => '✨',
            'status_changed' => '🔄',
            'progress_updated' => '📊',
            'assigned' => '👤',
            'reassigned' => '🔄',
            'completed' => '✅',
            'cancelled' => '❌',
            'updated' => '✏️',
            'commented' => '💬',
            'planner_started' => '▶️',
            'planner_paused' => '⏸️',
            'planner_resumed' => '▶️',
            'planner_postponed' => '⏭️',
            'planner_rescheduled' => '📅',
            'planner_synced' => '🔄',
            'followup_created' => '📞',
            'followup_completed' => '✅',
            'followup_rescheduled' => '📅',
            'followup_cancelled' => '❌',
            'followup_updated' => '✏️',
            'priority_changed' => '🔥',
            'deadline_changed' => '📅',
            'title_changed' => '✏️',
            'description_changed' => '📝',
            'type_changed' => '🏷️',
            'sla_changed' => '⏱️',
            'progress_changed' => '📊',
            default => '📝'
        };
    }
    
    private function getActionColor($action) {
        return match($action) {
            'created' => '#10b981',
            'status_changed' => '#3b82f6',
            'progress_updated' => '#8b5cf6',
            'assigned' => '#f59e0b',
            'reassigned' => '#f59e0b',
            'completed' => '#059669',
            'cancelled' => '#ef4444',
            'updated' => '#6b7280',
            'commented' => '#06b6d4',
            'planner_started' => '#10b981',
            'planner_paused' => '#f59e0b',
            'planner_resumed' => '#10b981',
            'planner_postponed' => '#8b5cf6',
            'planner_rescheduled' => '#3b82f6',
            'planner_synced' => '#6b7280',
            'followup_created' => '#06b6d4',
            'followup_completed' => '#059669',
            'followup_rescheduled' => '#3b82f6',
            'followup_cancelled' => '#ef4444',
            'followup_updated' => '#6b7280',
            'priority_changed' => '#f59e0b',
            'deadline_changed' => '#8b5cf6',
            'title_changed' => '#06b6d4',
            'description_changed' => '#6b7280',
            'type_changed' => '#10b981',
            'sla_changed' => '#f59e0b',
            'progress_changed' => '#8b5cf6',
            default => '#9ca3af'
        };
    }
    
    private function formatActionText($action) {
        return match($action) {
            'created' => 'Task Created',
            'status_changed' => 'Status Changed',
            'progress_updated' => 'Progress Updated',
            'assigned' => 'Task Assigned',
            'reassigned' => 'Task Reassigned',
            'completed' => 'Task Completed',
            'cancelled' => 'Task Cancelled',
            'updated' => 'Task Updated',
            'commented' => 'Comment Added',
            'planner_started' => 'Task Started',
            'planner_paused' => 'Task Paused',
            'planner_resumed' => 'Task Resumed',
            'planner_postponed' => 'Task Postponed',
            'planner_rescheduled' => 'Task Rescheduled',
            'planner_synced' => 'Planner Synced',
            'followup_created' => 'Follow-up Created',
            'followup_completed' => 'Follow-up Completed',
            'followup_rescheduled' => 'Follow-up Rescheduled',
            'followup_cancelled' => 'Follow-up Cancelled',
            'followup_updated' => 'Follow-up Updated',
            'priority_changed' => 'Priority Changed',
            'deadline_changed' => 'Deadline Changed',
            'title_changed' => 'Title Changed',
            'description_changed' => 'Description Changed',
            'type_changed' => 'Type Changed',
            'sla_changed' => 'SLA Changed',
            'progress_changed' => 'Progress Changed',
            default => ucfirst(str_replace('_', ' ', $action))
        };
    }
    
    private function formatTimeAgo($datetime) {
        // Convert to DateTime object for better timezone handling
        $date = new DateTime($datetime);
        $now = new DateTime();
        
        // Calculate the difference
        $interval = $now->diff($date);
        
        // Format the actual date and time in local timezone
        $fullDateTime = $date->format('M d, Y \a\t H:i:s');
        
        // Calculate total seconds difference
        $totalSeconds = ($now->getTimestamp() - $date->getTimestamp());
        
        if ($totalSeconds < 60) return 'Just now (' . $fullDateTime . ')';
        if ($totalSeconds < 3600) return floor($totalSeconds/60) . 'm ago (' . $fullDateTime . ')';
        if ($totalSeconds < 86400) return floor($totalSeconds/3600) . 'h ago (' . $fullDateTime . ')';
        if ($totalSeconds < 2592000) return $interval->days . 'd ago (' . $fullDateTime . ')';
        
        return $fullDateTime;
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
            
            $db->beginTransaction();
            
            // Get task info for logging before deletion
            $stmt = $db->prepare("SELECT title, assigned_to, assigned_by FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $taskInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete from followups table first (cascade delete for linked followups)
            $stmt = $db->prepare("DELETE FROM followups WHERE task_id = ?");
            $stmt->execute([$id]);
            $followupsDeleted = $stmt->rowCount();
            
            // Delete from daily_tasks (cascade delete for planner entries)
            $stmt = $db->prepare("DELETE FROM daily_tasks WHERE task_id = ? OR original_task_id = ?");
            $stmt->execute([$id, $id]);
            $plannerEntriesDeleted = $stmt->rowCount();
            
            // Delete from tasks table (main task record)
            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Log cascade deletion for audit trail
            if ($result && $taskInfo) {
                error_log("Task cascade deletion completed - Task ID: {$id}, Title: '{$taskInfo['title']}', Followups deleted: {$followupsDeleted}, Planner entries deleted: {$plannerEntriesDeleted}");
            }
            
            $db->commit();
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Task deleted successfully' : 'Delete failed']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    private function getDepartments() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure departments table exists
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                head_id INT NULL,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
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
            
            // Ensure projects table has department_id column
            $this->ensureProjectsTable($db);
            
            $stmt = $db->prepare("SELECT p.id, p.name, p.status, p.department_id, d.name as department_name FROM projects p LEFT JOIN departments d ON p.department_id = d.id WHERE p.status = 'active' ORDER BY p.name");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $projects;
        } catch (Exception $e) {
            error_log('Error fetching projects: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getContacts() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure contacts table exists
            $db->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $stmt = $db->prepare("SELECT id, name, phone, email, company FROM contacts ORDER BY name");
            $stmt->execute();
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $contacts;
        } catch (Exception $e) {
            error_log('Error fetching contacts: ' . $e->getMessage());
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
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS followups (
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
            )", "Create table");
            
            // Ensure contacts table exists
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
            // Add missing columns if they don't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                
                if (!in_array('followup_type', $columns)) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE followups ADD COLUMN followup_type ENUM('standalone','task') DEFAULT 'standalone' AFTER description", "Alter table");
                }
                
                if (!in_array('task_id', $columns)) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER followup_type", "Alter table");
                    DatabaseHelper::safeExec($db, "ALTER TABLE followups ADD INDEX idx_task_id (task_id)", "Alter table");
                }
                
                if (!in_array('contact_id', $columns)) {
                    DatabaseHelper::safeExec($db, "ALTER TABLE followups ADD COLUMN contact_id INT NULL AFTER task_id", "Alter table");
                    DatabaseHelper::safeExec($db, "ALTER TABLE followups ADD INDEX idx_contact_id (contact_id)", "Alter table");
                }
            } catch (Exception $e) {
                error_log('Column addition error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log('ensureFollowupsTable error: ' . $e->getMessage());
        }
    }
    
    private function logTaskHistory($db, $taskId, $action, $oldValue = null, $newValue = null, $notes = null, $userId = null, $timestamp = null) {
        try {
            $this->ensureTaskHistoryTable($db);
            
            // Use provided user ID or session user ID
            $createdBy = $userId ?? ($_SESSION['user_id'] ?? 1);
            
            // Use provided timestamp or current time
            if ($timestamp) {
                $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                return $stmt->execute([$taskId, $action, $oldValue, $newValue, $notes, $createdBy, $timestamp]);
            } else {
                $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                return $stmt->execute([$taskId, $action, $oldValue, $newValue, $notes, $createdBy]);
            }
        } catch (Exception $e) {
            error_log('Task history log error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function ensureProgressHistoryTable() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Create progress history table
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS task_progress_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                user_id INT NOT NULL,
                progress_from INT NOT NULL DEFAULT 0,
                progress_to INT NOT NULL,
                description TEXT,
                status_from VARCHAR(50),
                status_to VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_task_id (task_id),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            )", "Create table");
            
            // Add progress_description column to tasks table if not exists
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'progress_description'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN progress_description TEXT", "Alter table");
                error_log('Added progress_description column to tasks table');
            }
        } catch (Exception $e) {
            error_log('ensureProgressHistoryTable error: ' . $e->getMessage());
        }
    }
    
    private function ensureTaskHistoryTable($db) {
        try {
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS task_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_task_id (task_id)
            )", "Create table");
            
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
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS tasks (
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
            )", "Create table");
            
            // Check if department_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'department_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN department_id INT DEFAULT NULL", "Alter table");
                error_log('Added department_id column to tasks table');
            }
            
            // Check if task_category column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'task_category'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL", "Alter table");
                error_log('Added task_category column to tasks table');
            }
            
            // Check if project_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'project_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN project_id INT DEFAULT NULL", "Alter table");
                error_log('Added project_id column to tasks table');
            }
            
            // Check if followup_required column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'followup_required'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN followup_required TINYINT(1) DEFAULT 0", "Alter table");
                error_log('Added followup_required column to tasks table');
            }
            
            // Check if planned_date column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL", "Alter table");
                error_log('Added planned_date column to tasks table');
            }

            // Check if subcategory_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'subcategory_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN subcategory_id INT DEFAULT NULL", "Alter table");
                error_log('Added subcategory_id column to tasks table');
            }

            // Check if subcategory_id column exists, if not add it
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'subcategory_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks ADD COLUMN subcategory_id INT DEFAULT NULL", "Alter table");
                error_log('Added subcategory_id column to tasks table');
            }
            
            // Update sla_hours column to DECIMAL if it's still INT
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'sla_hours'");
            $stmt->execute();
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && strpos(strtolower($column['Type']), 'int') !== false) {
                DatabaseHelper::safeExec($db, "ALTER TABLE tasks MODIFY COLUMN sla_hours DECIMAL(8,4) DEFAULT 0.25", "Alter table");
                error_log('Updated sla_hours column to DECIMAL type');
            }
            

        } catch (Exception $e) {
            error_log('ensureTasksTable error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fallback method for static tasks when database is unavailable
     */
    private function syncWithPlanner($db, $taskId, $taskData) {
        try {
            // Use old assigned user from taskData if available
            $oldAssignedTo = $taskData['old_assigned_to'] ?? null;
            
            // Check if task was reassigned to a different user
            if ($oldAssignedTo && $oldAssignedTo != $taskData['assigned_to']) {
                // Remove task from previous user's planner
                $stmt = $db->prepare("DELETE FROM daily_tasks WHERE (original_task_id = ? OR task_id = ?) AND user_id = ?");
                $stmt->execute([$taskId, $taskId, $oldAssignedTo]);
                
                // Add task to new user's planner if planned_date is set
                if (!empty($taskData['planned_date'])) {
                    $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, original_task_id, title, description, scheduled_date, priority, status, completed_percentage, source_field, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned_date', NOW())");
                    $stmt->execute([
                        $taskData['assigned_to'],
                        $taskId,
                        $taskId,
                        $taskData['title'],
                        $taskData['description'],
                        $taskData['planned_date'],
                        $taskData['priority'],
                        $taskData['status'],
                        $taskData['progress']
                    ]);
                }
            } else {
                // Update existing entries for same user
                $stmt = $db->prepare("UPDATE daily_tasks SET 
                    title = ?, 
                    description = ?, 
                    user_id = ?, 
                    status = ?, 
                    completed_percentage = ?, 
                    priority = ?,
                    scheduled_date = COALESCE(?, scheduled_date)
                    WHERE (original_task_id = ? OR task_id = ?) AND user_id = ?");
                $stmt->execute([
                    $taskData['title'],
                    $taskData['description'],
                    $taskData['assigned_to'],
                    $taskData['status'],
                    $taskData['progress'],
                    $taskData['priority'],
                    $taskData['planned_date'],
                    $taskId,
                    $taskId,
                    $taskData['assigned_to']
                ]);
                
                // If no existing entries were updated and planned_date is set, create new entry
                if ($stmt->rowCount() == 0 && !empty($taskData['planned_date'])) {
                    $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, task_id, original_task_id, title, description, scheduled_date, priority, status, completed_percentage, source_field, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned_date', NOW())");
                    $stmt->execute([
                        $taskData['assigned_to'],
                        $taskId,
                        $taskId,
                        $taskData['title'],
                        $taskData['description'],
                        $taskData['planned_date'],
                        $taskData['priority'],
                        $taskData['status'],
                        $taskData['progress']
                    ]);
                }
            }
            
            error_log('Planner sync completed for task ID: ' . $taskId . ' (reassigned: ' . ($oldAssignedTo != $taskData['assigned_to'] ? 'yes' : 'no') . ')');
        } catch (Exception $e) {
            error_log('Planner sync error: ' . $e->getMessage());
        }
    }
    
    private function ensureProjectsTable($db) {
        try {
            // Create projects table if it doesn't exist
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                department_id INT DEFAULT NULL,
                status ENUM('active','inactive','completed') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
            // Add department_id column if it doesn't exist
            $stmt = $db->prepare("SHOW COLUMNS FROM projects LIKE 'department_id'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE projects ADD COLUMN department_id INT DEFAULT NULL", "Alter table");
                error_log('Added department_id column to projects table');
            }
            
            // Create some default projects if none exist
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM projects");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                $defaultProjects = [
                    ['name' => 'Website Development', 'description' => 'Company website development project', 'department_id' => 2],
                    ['name' => 'HR System Implementation', 'description' => 'Human resources management system', 'department_id' => 1],
                    ['name' => 'Marketing Campaign Q1', 'description' => 'First quarter marketing initiatives', 'department_id' => 4],
                    ['name' => 'Financial Audit 2024', 'description' => 'Annual financial audit process', 'department_id' => 3],
                    ['name' => 'Operations Optimization', 'description' => 'Streamline operational processes', 'department_id' => 5]
                ];
                
                $insertStmt = $db->prepare("INSERT INTO projects (name, description, department_id, status) VALUES (?, ?, ?, 'active')");
                foreach ($defaultProjects as $project) {
                    $insertStmt->execute([$project['name'], $project['description'], $project['department_id']]);
                }
                error_log('Created default projects with department associations');
            }
        } catch (Exception $e) {
            error_log('ensureProjectsTable error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log planner-related task actions (start, break, resume, postpone)
     */
    public static function logPlannerAction($taskId, $action, $details = '', $userId = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $controller = new self();
            $controller->ensureTaskHistoryTable($db);
            
            $actionMap = [
                'started' => 'Task started in planner',
                'paused' => 'Task paused/break taken',
                'resumed' => 'Task resumed from break',
                'postponed' => 'Task postponed to later',
                'rescheduled' => 'Task rescheduled to different date'
            ];
            
            $actionText = $actionMap[$action] ?? ucfirst($action);
            $timestamp = date('Y-m-d H:i:s');
            
            $notes = sprintf(
                '%s at %s%s',
                $actionText,
                $timestamp,
                $details ? ' | ' . $details : ''
            );
            
            $controller->logTaskHistory($db, $taskId, 'planner_' . $action, '', $actionText, $notes, $userId);
            
            error_log("Planner action logged: Task {$taskId} - {$action}");
            return true;
        } catch (Exception $e) {
            error_log('Planner action logging error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log followup-related task actions (completed, rescheduled, cancelled)
     */
    public static function logFollowupAction($taskId, $action, $followupDetails = '', $userId = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $controller = new self();
            $controller->ensureTaskHistoryTable($db);
            
            $actionMap = [
                'followup_completed' => 'Follow-up completed',
                'followup_rescheduled' => 'Follow-up rescheduled',
                'followup_cancelled' => 'Follow-up cancelled',
                'followup_created' => 'Follow-up created',
                'followup_updated' => 'Follow-up updated'
            ];
            
            $actionText = $actionMap[$action] ?? ucfirst(str_replace('_', ' ', $action));
            $timestamp = date('Y-m-d H:i:s');
            
            $notes = sprintf(
                '%s at %s%s',
                $actionText,
                $timestamp,
                $followupDetails ? ' | ' . $followupDetails : ''
            );
            
            $controller->logTaskHistory($db, $taskId, $action, '', $actionText, $notes, $userId);
            
            error_log("Followup action logged: Task {$taskId} - {$action}");
            return true;
        } catch (Exception $e) {
            error_log('Followup action logging error: ' . $e->getMessage());
            return false;
        }
    }
    
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
