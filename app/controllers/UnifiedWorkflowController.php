<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UnifiedWorkflowController extends Controller {
    

    
    public function dailyPlanner($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get tasks planned for this date from daily_planner table
            $plannedTasks = [];
            try {
                $stmt = $db->prepare("
                    SELECT dp.*, t.title as task_title, t.priority, t.progress as task_progress
                    FROM daily_planner dp
                    LEFT JOIN tasks t ON dp.task_id = t.id
                    WHERE dp.user_id = ? AND dp.date = ?
                    ORDER BY dp.priority_order
                ");
                $stmt->execute([$_SESSION['user_id'], $date]);
                $plannedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Daily planner table query failed: ' . $e->getMessage());
            }
            
            // If no planned tasks, get today's allocated tasks from tasks table
            if (empty($plannedTasks)) {
                try {
                    $stmt = $db->prepare("
                        SELECT 
                            t.id,
                            t.title,
                            t.description,
                            t.priority,
                            t.status,
                            t.progress as task_progress,
                            t.deadline,
                            u.name as assigned_user,
                            'allocated' as completion_status
                        FROM tasks t
                        LEFT JOIN users u ON t.assigned_to = u.id
                        WHERE t.assigned_to = ? 
                        AND (DATE(t.deadline) = ? OR DATE(t.created_at) = ? OR t.status IN ('assigned', 'in_progress'))
                        ORDER BY 
                            CASE t.priority 
                                WHEN 'high' THEN 1 
                                WHEN 'medium' THEN 2 
                                ELSE 3 
                            END,
                            t.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$_SESSION['user_id'], $date, $date]);
                    $allocatedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Convert allocated tasks to planner format
                    foreach ($allocatedTasks as $task) {
                        $plannedTasks[] = [
                            'id' => 'task_' . $task['id'],
                            'task_id' => $task['id'],
                            'title' => $task['title'],
                            'description' => $task['description'],
                            'priority' => $task['priority'],
                            'task_progress' => $task['task_progress'],
                            'completion_status' => $task['status'] === 'completed' ? 'completed' : 'not_started',
                            'planned_start_time' => null,
                            'planned_duration' => null,
                            'notes' => '',
                            'task_title' => $task['title'],
                            'source' => 'tasks_table'
                        ];
                    }
                } catch (Exception $e) {
                    error_log('Tasks table query failed: ' . $e->getMessage());
                }
            }
            
            // Add dummy data if still no tasks exist
            if (empty($plannedTasks) && $date === date('Y-m-d')) {
                $plannedTasks = $this->getDummyPlannerTasks($date);
            }
            
            $data = [
                'planned_tasks' => $plannedTasks,
                'selected_date' => $date,
                'active_page' => 'daily-planner'
            ];
            
            $this->view('daily_workflow/unified_daily_planner', $data);
        } catch (Exception $e) {
            error_log('Daily planner error: ' . $e->getMessage());
            $this->view('daily_workflow/unified_daily_planner', ['planned_tasks' => [], 'selected_date' => $date]);
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
            
            // Add dummy data if no tasks exist
            if (empty($todayTasks) && $date === date('Y-m-d')) {
                $todayTasks = $this->getDummyEveningTasks($date);
            }
            
            $data = [
                'today_tasks' => $todayTasks,
                'existing_update' => $existingUpdate,
                'selected_date' => $date,
                'active_page' => 'evening-update'
            ];
            
            $this->view('evening-update/unified_index', $data);
        } catch (Exception $e) {
            error_log('Evening update error: ' . $e->getMessage());
            $this->view('evening-update/unified_index', ['today_tasks' => [], 'selected_date' => $date]);
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
            
            // Add dummy data if no followups exist
            if (empty($followupTasks)) {
                $followupTasks = $this->getDummyFollowupTasks();
            }
            
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
                WHERE (MONTH(t.planned_date) = ? AND YEAR(t.planned_date) = ?) 
                   OR (t.planned_date IS NULL AND DATE(t.created_at) = CURDATE())
                AND (t.assigned_to = ? OR t.assigned_by = ?)
                
                UNION ALL
                
                SELECT 
                    dp.id, dp.title, dp.priority, 'planned' as status, dp.plan_date as date,
                    u.name as assigned_user, 'planner' as type
                FROM daily_planner dp
                LEFT JOIN users u ON dp.user_id = u.id
                WHERE MONTH(dp.plan_date) = ? AND YEAR(dp.plan_date) = ?
                AND dp.user_id = ?
                
                ORDER BY date
            ");
            
            $stmt->execute([
                $month, $year, $_SESSION['user_id'], $_SESSION['user_id'],
                $month, $year, $_SESSION['user_id']
            ]);
            
            $calendarTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add dummy data if no tasks exist
            if (empty($calendarTasks)) {
                $calendarTasks = $this->getDummyCalendarTasks($month, $year);
            }
            
            $data = [
                'calendar_tasks' => $calendarTasks,
                'current_month' => $month,
                'current_year' => $year,
                'active_page' => 'calendar'
            ];
            
            $this->view('tasks/unified_calendar', $data);
        } catch (Exception $e) {
            error_log('Calendar error: ' . $e->getMessage());
            $this->view('tasks/unified_calendar', ['calendar_tasks' => [], 'current_month' => $month, 'current_year' => $year]);
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
                    dp.id, dp.title, dp.priority, 'planned' as status, 'planner' as type
                FROM daily_planner dp
                WHERE dp.plan_date = ? AND dp.user_id = ?
                
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
    
    private function getDummyPlannerTasks($date) {
        return [
            [
                'id' => 'dummy_1',
                'title' => 'Morning Email Review',
                'description' => 'Check and respond to overnight emails',
                'planned_start_time' => '08:30:00',
                'planned_duration' => 30,
                'priority' => 'medium',
                'completion_status' => 'not_started',
                'task_progress' => 0
            ],
            [
                'id' => 'dummy_2',
                'title' => 'Daily Standup Meeting',
                'description' => 'Team synchronization meeting',
                'planned_start_time' => '09:30:00',
                'planned_duration' => 30,
                'priority' => 'high',
                'completion_status' => 'not_started',
                'task_progress' => 0
            ],
            [
                'id' => 'dummy_3',
                'title' => 'Feature Development',
                'description' => 'Work on new dashboard features',
                'planned_start_time' => '10:00:00',
                'planned_duration' => 180,
                'priority' => 'high',
                'completion_status' => 'not_started',
                'task_progress' => 0
            ],
            [
                'id' => 'dummy_4',
                'title' => 'Code Review Session',
                'description' => 'Review team members pull requests',
                'planned_start_time' => '14:00:00',
                'planned_duration' => 90,
                'priority' => 'medium',
                'completion_status' => 'not_started',
                'task_progress' => 0
            ]
        ];
    }
    
    private function getDummyEveningTasks($date) {
        return [
            [
                'id' => 'dummy_1',
                'title' => 'Morning Email Review',
                'description' => 'Check and respond to overnight emails',
                'completion_status' => 'completed',
                'task_progress' => 100,
                'task_id' => null,
                'notes' => 'Completed all email responses'
            ],
            [
                'id' => 'dummy_2',
                'title' => 'Daily Standup Meeting',
                'description' => 'Team synchronization meeting',
                'completion_status' => 'completed',
                'task_progress' => 100,
                'task_id' => null,
                'notes' => 'Good team sync, discussed blockers'
            ],
            [
                'id' => 'dummy_3',
                'title' => 'Feature Development',
                'description' => 'Work on new dashboard features',
                'completion_status' => 'in_progress',
                'task_progress' => 75,
                'task_id' => null,
                'notes' => 'Made good progress, need to finish testing'
            ],
            [
                'id' => 'dummy_4',
                'title' => 'Code Review Session',
                'description' => 'Review team members pull requests',
                'completion_status' => 'postponed',
                'task_progress' => 0,
                'task_id' => null,
                'notes' => 'Postponed due to urgent bug fix'
            ]
        ];
    }
    
    private function getDummyFollowupTasks() {
        return [
            [
                'id' => 'dummy_f1',
                'title' => 'Follow-up: Client Project Status',
                'description' => 'Check on project milestone completion and address any blockers',
                'priority' => 'high',
                'status' => 'assigned',
                'task_category' => 'Follow-up',
                'assigned_user' => $_SESSION['user_name'] ?? 'Current User',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'dummy_f2',
                'title' => 'Follow-up: Training Session Feedback',
                'description' => 'Gather feedback on training effectiveness and areas for improvement',
                'priority' => 'medium',
                'status' => 'assigned',
                'task_category' => 'Follow-up',
                'assigned_user' => $_SESSION['user_name'] ?? 'Current User',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'dummy_f3',
                'title' => 'Follow-up: Vendor Contract Review',
                'description' => 'Review and follow up on pending vendor contract negotiations',
                'priority' => 'medium',
                'status' => 'assigned',
                'task_category' => 'Follow-up',
                'assigned_user' => $_SESSION['user_name'] ?? 'Current User',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    private function getDummyCalendarTasks($month, $year) {
        $tasks = [];
        $currentDate = date('Y-m-d');
        
        // Generate some dummy tasks for the current month
        for ($day = 1; $day <= 28; $day += 3) {
            $taskDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            $tasks[] = [
                'id' => 'cal_dummy_' . $day,
                'title' => 'Team Meeting',
                'priority' => 'medium',
                'status' => 'assigned',
                'date' => $taskDate,
                'assigned_user' => $_SESSION['user_name'] ?? 'Current User',
                'type' => 'task'
            ];
            
            if ($day + 1 <= 28) {
                $tasks[] = [
                    'id' => 'cal_dummy_' . ($day + 1),
                    'title' => 'Project Review',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'date' => sprintf('%04d-%02d-%02d', $year, $month, $day + 1),
                    'assigned_user' => $_SESSION['user_name'] ?? 'Current User',
                    'type' => 'task'
                ];
            }
        }
        
        return $tasks;
    }
}
?>