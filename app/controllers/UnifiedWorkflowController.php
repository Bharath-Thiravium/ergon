<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UnifiedWorkflowController extends Controller {
    

    
    public function dailyPlanner($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            
            $plannedTasks = $planner->getTasksForDate($_SESSION['user_id'], $date);
            $dailyStats = $planner->getDailyStats($_SESSION['user_id'], $date);
            

            
            $data = [
                'planned_tasks' => $plannedTasks,
                'daily_stats' => $dailyStats,
                'selected_date' => $date,
                'active_page' => 'daily-planner'
            ];
            
            $this->view('daily_workflow/unified_daily_planner', $data);
        } catch (Exception $e) {
            error_log('Daily planner error: ' . $e->getMessage());
            $this->view('daily_workflow/unified_daily_planner', [
                'planned_tasks' => [], 
                'daily_stats' => [],
                'selected_date' => $date
            ]);
        }
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
    
    public function startTask() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if task exists and belongs to user
            $checkStmt = $db->prepare("SELECT id, assigned_to FROM tasks WHERE id = ?");
            $checkStmt->execute([$taskId]);
            $task = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            if ($task['assigned_to'] != $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Task not assigned to you']);
                return;
            }
            
            // Update task status
            $stmt = $db->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
            $result = $stmt->execute([$taskId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task started successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update task']);
            }
        } catch (Exception $e) {
            error_log('Start task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    public function pauseTask() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ? AND assigned_to = ?");
            $result = $stmt->execute([$taskId, $_SESSION['user_id']]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task paused successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to pause task']);
            }
        } catch (Exception $e) {
            error_log('Pause task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    public function resumeTask() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            
            if ($planner->resumeTask($taskId, $_SESSION['user_id'])) {
                echo json_encode(['success' => true, 'message' => 'Task resumed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to resume task']);
            }
        } catch (Exception $e) {
            error_log('Resume task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error resuming task']);
        }
    }
    
    public function completeTask() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        $percentage = $input['percentage'] ?? 100;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE tasks SET status = 'completed', progress = ? WHERE id = ? AND assigned_to = ?");
            $result = $stmt->execute([$percentage, $taskId, $_SESSION['user_id']]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task completed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to complete task']);
            }
        } catch (Exception $e) {
            error_log('Complete task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    public function postponeTask() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? null;
        $newDate = $input['new_date'] ?? null;
        
        if (!$taskId || !$newDate) {
            echo json_encode(['success' => false, 'message' => 'Task ID and new date required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            
            if ($planner->postponeTask($taskId, $_SESSION['user_id'], $newDate)) {
                echo json_encode(['success' => true, 'message' => 'Task postponed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to postpone task']);
            }
        } catch (Exception $e) {
            error_log('Postpone task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error postponing task']);
        }
    }
    
    public function getTaskTimer() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $taskId = $_GET['task_id'] ?? null;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("
                SELECT active_seconds, start_time, resume_time, status
                FROM daily_tasks 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$taskId, $_SESSION['user_id']]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                $currentActiveTime = 0;
                if ($task['status'] === 'in_progress') {
                    $startTime = $task['resume_time'] ?: $task['start_time'];
                    if ($startTime) {
                        $currentActiveTime = time() - strtotime($startTime);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'active_seconds' => $task['active_seconds'] + $currentActiveTime,
                    'status' => $task['status']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
            }
        } catch (Exception $e) {
            error_log('Get task timer error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error getting timer']);
        }
    }
    
    public function updateTaskStatus() {
        // Legacy method - redirect to appropriate new methods
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $input['status'] ?? null;
        
        switch ($action) {
            case 'start':
            case 'in_progress':
                return $this->startTask();
            case 'pause':
            case 'paused':
                return $this->pauseTask();
            case 'resume':
                return $this->resumeTask();
            case 'complete':
            case 'completed':
                return $this->completeTask();
            case 'postpone':
            case 'postponed':
                return $this->postponeTask();
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $scheduledDate = $_POST['scheduled_date'] ?? date('Y-m-d');
        $plannedTime = $_POST['planned_time'] ?? null;
        $duration = intval($_POST['duration'] ?? 60);
        $priority = $_POST['priority'] ?? 'medium';
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure daily_tasks table exists
            $this->ensureDailyTasksTable($db);
            
            // Create daily task entry
            $stmt = $db->prepare("
                INSERT INTO daily_tasks 
                (user_id, scheduled_date, title, description, planned_start_time, planned_duration, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
            ");
            
            $result = $stmt->execute([
                $_SESSION['user_id'], $scheduledDate, $title, $description, 
                $plannedTime, $duration, $priority
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add task']);
            }
        } catch (Exception $e) {
            error_log('Quick add task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    private function ensureDailyTasksTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS daily_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                scheduled_date DATE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                planned_start_time TIME NULL,
                planned_duration INT DEFAULT 60,
                priority ENUM('low','medium','high') DEFAULT 'medium',
                status ENUM('not_started','in_progress','paused','completed','postponed') DEFAULT 'not_started',
                start_time TIMESTAMP NULL,
                pause_time TIMESTAMP NULL,
                resume_time TIMESTAMP NULL,
                completion_time TIMESTAMP NULL,
                active_seconds INT DEFAULT 0,
                completed_percentage INT DEFAULT 0,
                postponed_from_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, scheduled_date),
                INDEX idx_status (status)
            )");
        } catch (Exception $e) {
            error_log('ensureDailyTasksTable error: ' . $e->getMessage());
        }
    }
    

    

    

    

}
?>