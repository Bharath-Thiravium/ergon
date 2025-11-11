<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UnifiedWorkflowController extends Controller {
    
    public function createTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->storeTask();
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
    
    private function storeTask() {
        $taskData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'assigned_by' => $_SESSION['user_id'],
            'assigned_to' => intval($_POST['assigned_to'] ?? $_SESSION['user_id']),
            'assigned_for' => $_POST['assigned_for'] ?? 'self',
            'task_type' => $_POST['task_type'] ?? 'ad-hoc',
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
            'planned_date' => !empty($_POST['planned_date']) ? $_POST['planned_date'] : null,
            'status' => $_POST['status'] ?? 'assigned',
            'progress' => intval($_POST['progress'] ?? 0),
            'followup_required' => isset($_POST['followup_required']) ? 1 : 0,
            'sla_hours' => intval($_POST['sla_hours'] ?? 24),
            'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'task_category' => trim($_POST['task_category'] ?? '')
        ];
        
        if (empty($taskData['title'])) {
            header('Location: /ergon/workflow/create-task?error=Title is required');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Insert task
            $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, assigned_for, task_type, priority, deadline, planned_date, status, progress, followup_required, sla_hours, department_id, task_category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $result = $stmt->execute([
                $taskData['title'], $taskData['description'], $taskData['assigned_by'], 
                $taskData['assigned_to'], $taskData['assigned_for'], $taskData['task_type'],
                $taskData['priority'], $taskData['deadline'], $taskData['planned_date'],
                $taskData['status'], $taskData['progress'], $taskData['followup_required'],
                $taskData['sla_hours'], $taskData['department_id'], $taskData['task_category']
            ]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                // Auto-create daily planner entry if planned_date is set
                if (!empty($taskData['planned_date'])) {
                    $this->createPlannerEntry($db, $taskId, $taskData);
                }
                
                header('Location: /ergon/tasks?success=Task created successfully');
            } else {
                header('Location: /ergon/workflow/create-task?error=Failed to create task');
            }
        } catch (Exception $e) {
            error_log('Task creation error: ' . $e->getMessage());
            header('Location: /ergon/workflow/create-task?error=Task creation failed');
        }
        exit;
    }
    
    public function dailyPlanner($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get tasks planned for this date
            $stmt = $db->prepare("
                SELECT dp.*, t.title as task_title, t.priority, t.progress as task_progress
                FROM daily_planner dp
                LEFT JOIN tasks t ON dp.task_id = t.id
                WHERE dp.user_id = ? AND dp.date = ?
                ORDER BY dp.priority_order
            ");
            $stmt->execute([$_SESSION['user_id'], $date]);
            $plannedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'planned_tasks' => $plannedTasks,
                'selected_date' => $date,
                'active_page' => 'daily-planner'
            ];
            
            $this->view('daily_workflow/daily_planner', $data);
        } catch (Exception $e) {
            error_log('Daily planner error: ' . $e->getMessage());
            $this->view('daily_workflow/daily_planner', ['planned_tasks' => [], 'selected_date' => $date]);
        }
    }
    
    public function eveningUpdate($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->storeEveningUpdate($date);
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get today's planned tasks for update
            $stmt = $db->prepare("
                SELECT dp.*, t.title as task_title, t.priority
                FROM daily_planner dp
                LEFT JOIN tasks t ON dp.task_id = t.id
                WHERE dp.user_id = ? AND dp.date = ?
                ORDER BY dp.priority_order
            ");
            $stmt->execute([$_SESSION['user_id'], $date]);
            $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get existing evening update
            $stmt = $db->prepare("SELECT * FROM evening_updates WHERE user_id = ? AND DATE(created_at) = ?");
            $stmt->execute([$_SESSION['user_id'], $date]);
            $existingUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data = [
                'today_tasks' => $todayTasks,
                'existing_update' => $existingUpdate,
                'selected_date' => $date,
                'active_page' => 'evening-update'
            ];
            
            $this->view('evening-update/index', $data);
        } catch (Exception $e) {
            error_log('Evening update error: ' . $e->getMessage());
            $this->view('evening-update/index', ['today_tasks' => [], 'selected_date' => $date]);
        }
    }
    
    private function storeEveningUpdate($date) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Update task completion status
            if (isset($_POST['task_updates'])) {
                foreach ($_POST['task_updates'] as $plannerId => $update) {
                    $stmt = $db->prepare("UPDATE daily_planner SET completion_status = ?, notes = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([
                        $update['completion_status'],
                        $update['notes'] ?? '',
                        $plannerId,
                        $_SESSION['user_id']
                    ]);
                    
                    // Update task progress if linked
                    if (!empty($update['task_id']) && isset($update['progress'])) {
                        $stmt = $db->prepare("UPDATE tasks SET progress = ?, status = ? WHERE id = ?");
                        $status = $update['progress'] == 100 ? 'completed' : 'in_progress';
                        $stmt->execute([$update['progress'], $status, $update['task_id']]);
                    }
                }
            }
            
            // Store evening update
            $stmt = $db->prepare("
                INSERT INTO evening_updates (user_id, title, accomplishments, challenges, tomorrow_plan, overall_productivity, planner_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                accomplishments = VALUES(accomplishments),
                challenges = VALUES(challenges),
                tomorrow_plan = VALUES(tomorrow_plan),
                overall_productivity = VALUES(overall_productivity),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['title'] ?? 'Daily Update',
                $_POST['accomplishments'] ?? '',
                $_POST['challenges'] ?? '',
                $_POST['tomorrow_plan'] ?? '',
                intval($_POST['overall_productivity'] ?? 0),
                $date
            ]);
            
            // Create followups for incomplete tasks
            $this->createFollowupsFromIncomplete($db, $date);
            
            header('Location: /ergon/workflow/evening-update?success=Update saved successfully');
        } catch (Exception $e) {
            error_log('Evening update store error: ' . $e->getMessage());
            header('Location: /ergon/workflow/evening-update?error=Failed to save update');
        }
        exit;
    }
    
    public function followups() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get followup tasks
            $stmt = $db->prepare("
                SELECT t.*, u.name as assigned_user
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE (t.followup_required = 1 OR t.task_category LIKE '%follow%')
                AND t.assigned_to = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $followupTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'followup_tasks' => $followupTasks,
                'active_page' => 'followups'
            ];
            
            $this->view('followups/index', $data);
        } catch (Exception $e) {
            error_log('Followups error: ' . $e->getMessage());
            $this->view('followups/index', ['followup_tasks' => []]);
        }
    }
    
    public function calendar() {
        AuthMiddleware::requireAuth();
        
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get tasks for the month
            $stmt = $db->prepare("
                SELECT 
                    t.id, t.title, t.priority, t.status, t.planned_date as date,
                    u.name as assigned_user, 'task' as type
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE MONTH(t.planned_date) = ? AND YEAR(t.planned_date) = ?
                AND (t.assigned_to = ? OR t.assigned_by = ?)
                
                UNION ALL
                
                SELECT 
                    dp.id, dp.title, 'medium' as priority, dp.status, dp.date,
                    u.name as assigned_user, 'planner' as type
                FROM daily_planner dp
                LEFT JOIN users u ON dp.user_id = u.id
                WHERE MONTH(dp.date) = ? AND YEAR(dp.date) = ?
                AND dp.user_id = ?
                
                ORDER BY date
            ");
            
            $stmt->execute([
                $month, $year, $_SESSION['user_id'], $_SESSION['user_id'],
                $month, $year, $_SESSION['user_id']
            ]);
            
            $calendarTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'calendar_tasks' => $calendarTasks,
                'current_month' => $month,
                'current_year' => $year,
                'active_page' => 'calendar'
            ];
            
            $this->view('tasks/calendar', $data);
        } catch (Exception $e) {
            error_log('Calendar error: ' . $e->getMessage());
            $this->view('tasks/calendar', ['calendar_tasks' => [], 'current_month' => $month, 'current_year' => $year]);
        }
    }
    
    private function createPlannerEntry($db, $taskId, $taskData) {
        try {
            $stmt = $db->prepare("
                INSERT INTO daily_planner (user_id, task_id, date, title, description, priority_order, status, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 'planned', NOW())
            ");
            
            $stmt->execute([
                $taskData['assigned_to'],
                $taskId,
                $taskData['planned_date'],
                $taskData['title'],
                $taskData['description']
            ]);
        } catch (Exception $e) {
            error_log('Planner entry creation error: ' . $e->getMessage());
        }
    }
    
    private function createFollowupsFromIncomplete($db, $date) {
        try {
            // Get incomplete tasks from today
            $stmt = $db->prepare("
                SELECT dp.*, t.id as task_id, t.title as task_title
                FROM daily_planner dp
                LEFT JOIN tasks t ON dp.task_id = t.id
                WHERE dp.user_id = ? AND dp.date = ? 
                AND dp.completion_status IN ('not_started', 'in_progress')
            ");
            $stmt->execute([$_SESSION['user_id'], $date]);
            $incompleteTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($incompleteTasks as $task) {
                if (!empty($task['task_id'])) {
                    // Mark task as requiring followup
                    $stmt = $db->prepare("UPDATE tasks SET followup_required = 1 WHERE id = ?");
                    $stmt->execute([$task['task_id']]);
                }
            }
        } catch (Exception $e) {
            error_log('Followup creation error: ' . $e->getMessage());
        }
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if (($_SESSION['role'] ?? '') === 'user') {
                $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching users: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getDepartments() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching departments: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateTaskStatus() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        $status = $input['status'] ?? null;
        $date = $input['date'] ?? date('Y-m-d');
        
        if (!$taskId || !$status) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Update daily planner status
            $stmt = $db->prepare("UPDATE daily_planner SET completion_status = ? WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (Exception $e) {
            error_log('Update task status error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
    
    public function getTasksForDate() {
        AuthMiddleware::requireAuth();
        
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("
                SELECT 
                    t.id, t.title, t.priority, t.status, 'task' as type
                FROM tasks t
                WHERE t.planned_date = ? AND (t.assigned_to = ? OR t.assigned_by = ?)
                
                UNION ALL
                
                SELECT 
                    dp.id, dp.title, 'medium' as priority, dp.status, 'planner' as type
                FROM daily_planner dp
                WHERE dp.date = ? AND dp.user_id = ?
                
                ORDER BY title
            ");
            
            $stmt->execute([$date, $_SESSION['user_id'], $_SESSION['user_id'], $date, $_SESSION['user_id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['tasks' => $tasks]);
        } catch (Exception $e) {
            error_log('Get tasks for date error: ' . $e->getMessage());
            echo json_encode(['tasks' => []]);
        }
    }
    
    public function quickAddTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $title = trim($_POST['title'] ?? '');
        $plannedDate = $_POST['planned_date'] ?? date('Y-m-d');
        $plannedTime = $_POST['planned_time'] ?? null;
        $priority = $_POST['priority'] ?? 'medium';
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Create task
            $stmt = $db->prepare("
                INSERT INTO tasks (title, assigned_by, assigned_to, assigned_for, priority, planned_date, status, created_at)
                VALUES (?, ?, ?, 'self', ?, ?, 'assigned', NOW())
            ");
            
            $result = $stmt->execute([$title, $_SESSION['user_id'], $_SESSION['user_id'], $priority, $plannedDate]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                // Create planner entry
                $stmt = $db->prepare("
                    INSERT INTO daily_planner (user_id, task_id, date, title, planned_start_time, priority_order, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 1, 'planned', NOW())
                ");
                
                $stmt->execute([$_SESSION['user_id'], $taskId, $plannedDate, $title, $plannedTime]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create task']);
            }
        } catch (Exception $e) {
            error_log('Quick add task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
?>