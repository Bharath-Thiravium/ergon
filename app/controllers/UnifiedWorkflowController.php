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
            
            // Ensure daily_tasks table exists
            $this->ensureDailyTasksTable($db);
            
            // Get daily tasks for the selected date with fallback
            try {
                $stmt = $db->prepare("
                    SELECT dt.*, 
                           COALESCE(dt.planned_duration, 60) as planned_duration_minutes
                    FROM daily_tasks dt 
                    WHERE dt.user_id = ? AND dt.scheduled_date = ? 
                    ORDER BY 
                        CASE dt.status 
                            WHEN 'in_progress' THEN 1 
                            WHEN 'on_break' THEN 2 
                            WHEN 'not_started' THEN 3 
                            WHEN 'completed' THEN 4 
                            ELSE 5 
                        END, dt.created_at ASC
                ");
                $stmt->execute([$_SESSION['user_id'], $date]);
                $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Daily tasks complex query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                $stmt->execute([$_SESSION['user_id'], $date]);
                $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // If no daily tasks, create them from regular tasks
            if (empty($dailyTasks)) {
                try {
                    $stmt = $db->prepare("SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute([$_SESSION['user_id']]);
                    $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log('Regular tasks query failed: ' . $e->getMessage());
                    $regularTasks = [];
                }
                
                // Create daily tasks from regular tasks
                foreach ($regularTasks as $task) {
                    $stmt = $db->prepare("
                        INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
                        VALUES (?, ?, ?, ?, ?, 60, ?, 'not_started', NOW())
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'], 
                        $task['id'], 
                        $date, 
                        $task['title'], 
                        $task['description'], 
                        $task['priority'] ?? 'medium'
                    ]);
                }
                
                // Re-fetch daily tasks
                try {
                    $stmt = $db->prepare("
                        SELECT dt.*, 
                               COALESCE(dt.planned_duration, 60) as planned_duration_minutes
                        FROM daily_tasks dt 
                        WHERE dt.user_id = ? AND dt.scheduled_date = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $date]);
                    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log('Re-fetch daily tasks failed: ' . $e->getMessage());
                    $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                    $stmt->execute([$_SESSION['user_id'], $date]);
                    $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            $plannedTasks = [];
            foreach ($dailyTasks as $task) {
                $plannedDuration = $task['planned_duration_minutes'] ?? $task['planned_duration'] ?? 60;
                $plannedTasks[] = [
                    'id' => $task['id'],
                    'task_id' => $task['task_id'] ?? null,
                    'title' => $task['title'] ?? 'Untitled Task',
                    'description' => $task['description'] ?? '',
                    'priority' => $task['priority'] ?? 'medium',
                    'status' => $task['status'] ?? 'not_started',
                    'sla_hours' => max(1, $plannedDuration / 60),
                    'start_time' => $task['start_time'] ?? null,
                    'planned_duration' => $plannedDuration,
                    'completed_percentage' => $task['completed_percentage'] ?? 0
                ];
            }
            
            $dailyStats = [
                'total_tasks' => count($plannedTasks),
                'completed_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'completed')),
                'in_progress_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'in_progress')),
                'postponed_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'postponed')),
                'total_planned_minutes' => array_sum(array_map(fn($t) => ($t['sla_hours'] ?? 1) * 60, $plannedTasks)),
                'total_active_seconds' => 0,
                'avg_completion' => 0
            ];
            
            $this->view('daily_workflow/unified_daily_planner', [
                'planned_tasks' => $plannedTasks,
                'daily_stats' => $dailyStats,
                'selected_date' => $date,
                'active_page' => 'daily-planner'
            ]);
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
            
            // Get actual followups from followups table with fallback
            try {
                if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                    $stmt = $db->prepare("
                        SELECT f.*, u.name as assigned_user 
                        FROM followups f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        ORDER BY f.follow_up_date ASC
                    ");
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("
                        SELECT f.*, u.name as assigned_user 
                        FROM followups f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        WHERE f.user_id = ? 
                        ORDER BY f.follow_up_date ASC
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Followups complex query failed, using fallback: ' . $e->getMessage());
                if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                    $stmt = $db->prepare("SELECT * FROM followups ORDER BY follow_up_date ASC");
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Calculate KPIs
            $today = date('Y-m-d');
            $overdue = $today_count = $completed = 0;
            
            foreach ($followups as $followup) {
                if ($followup['status'] === 'completed') {
                    $completed++;
                } elseif ($followup['follow_up_date'] < $today) {
                    $overdue++;
                } elseif ($followup['follow_up_date'] === $today) {
                    $today_count++;
                }
            }
            
            $data = [
                'followups' => $followups,
                'overdue' => $overdue,
                'today_count' => $today_count,
                'completed' => $completed,
                'active_page' => 'followups'
            ];
            
            $this->view('followups/index', $data);
        } catch (Exception $e) {
            error_log('Followups error: ' . $e->getMessage());
            $this->view('followups/index', ['followups' => [], 'active_page' => 'followups']);
        }
    }
    
    // Calendar functionality moved to TasksController::getTaskSchedule()
    // This method redirects to the new task visualization layer
    public function calendar() {
        header('Location: /ergon/tasks/schedule');
        exit;
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
            
            // Ensure table exists
            $this->ensureDailyTasksTable($db);
            
            $status = $this->validateStatus('in_progress');
            
            // Try complex update first, fallback to simple update
            try {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, start_time = NOW(), resume_time = NULL WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log('Complex start task query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            }
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Task started successfully',
                    'start_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Task not found or already started']);
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
            
            // Ensure table exists
            $this->ensureDailyTasksTable($db);
            
            $status = $this->validateStatus('on_break');
            
            // Try complex update first, fallback to simple update
            try {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, pause_time = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log('Complex pause task query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            }
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Task paused successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Task not found or not in progress']);
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
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $this->ensureDailyTasksTable($db);
            $status = $this->validateStatus('in_progress');
            
            // Resume restarts timer from zero - reset start_time to NOW
            try {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, start_time = NOW(), resume_time = NULL, active_seconds = 0 WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log('Complex resume task query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, start_time = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            }
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Task resumed - timer restarted',
                    'start_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Task not found or not paused']);
            }
        } catch (Exception $e) {
            error_log('Resume task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
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
            
            $this->ensureDailyTasksTable($db);
            $status = $this->validateStatus('completed');
            
            $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, completed_percentage = ?, completion_time = NOW() WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$status, $percentage, $taskId, $_SESSION['user_id']]);
            
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
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $input['status'] ?? null;
        $taskId = $input['task_id'] ?? null;
        
        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Task ID required']);
            return;
        }
        
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
                // Handle completion with percentage
                $percentage = $input['percentage'] ?? 100;
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::connect();
                    
                    $this->ensureDailyTasksTable($db);
                    
                    if ($percentage < 100) {
                        // Partial completion - defer to next working day
                        $nextWorkingDay = $this->getNextWorkingDay();
                        $status = $this->validateStatus('in_progress');
                        
                        $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, completed_percentage = ?, scheduled_date = ?, postponed_from_date = scheduled_date WHERE id = ? AND user_id = ?");
                        $result = $stmt->execute([$status, $percentage, $nextWorkingDay, $taskId, $_SESSION['user_id']]);
                        
                        $message = "Task {$percentage}% complete - deferred to {$nextWorkingDay}";
                    } else {
                        // Full completion
                        $status = $this->validateStatus('completed');
                        
                        $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, completed_percentage = ?, completion_time = NOW() WHERE id = ? AND user_id = ?");
                        $result = $stmt->execute([$status, $percentage, $taskId, $_SESSION['user_id']]);
                        
                        $message = 'Task completed successfully';
                    }
                    
                    if ($result && $stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => $message, 'percentage' => $percentage]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Task not found']);
                    }
                } catch (Exception $e) {
                    error_log('Complete task error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                return;
            case 'postpone':
            case 'postponed':
                return $this->postponeTask();
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    public function getTasksForDate() {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("
                SELECT 
                    t.id, t.title, t.description, t.priority, t.status, t.progress, t.task_type, t.task_category,
                    t.company_name, t.contact_person, t.project_name, t.deadline, t.planned_date, t.assigned_at, t.created_at,
                    u.name as assigned_by_user, d.name as department_name, 'task' as type
                FROM tasks t
                LEFT JOIN users u ON t.assigned_by = u.id
                LEFT JOIN departments d ON t.department_id = d.id
                WHERE t.assigned_to = ?
                AND (
                    DATE(t.planned_date) = ? OR 
                    DATE(t.deadline) = ? OR 
                    DATE(t.assigned_at) = ? OR 
                    DATE(t.created_at) = ?
                )
            ");
            $stmt->execute([$_SESSION['user_id'], $date, $date, $date, $date]);
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
            // Create table with VARCHAR for better compatibility
            $createSQL = "CREATE TABLE IF NOT EXISTS daily_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                scheduled_date DATE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                planned_start_time TIME NULL,
                planned_duration INT DEFAULT 60,
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(50) DEFAULT 'not_started',
                start_time TIMESTAMP NULL,
                pause_time TIMESTAMP NULL,
                resume_time TIMESTAMP NULL,
                completion_time TIMESTAMP NULL,
                active_seconds INT DEFAULT 0,
                completed_percentage INT DEFAULT 0,
                postponed_from_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $db->exec($createSQL);
            
            // Check if status column needs to be modified
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'status'");
                $stmt->execute();
                $column = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($column && (strpos($column['Type'], 'enum') !== false || strpos($column['Type'], 'varchar(20)') !== false)) {
                    $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN status VARCHAR(50) DEFAULT 'not_started'");
                    error_log('Modified status column to VARCHAR(50)');
                }
            } catch (Exception $e) {
                error_log('Status column modification error (non-critical): ' . $e->getMessage());
            }
            
            // Normalize existing status values
            $this->normalizeStatusValues($db);
            
            // Add indexes separately
            try {
                $db->exec("CREATE INDEX IF NOT EXISTS idx_user_date ON daily_tasks (user_id, scheduled_date)");
                $db->exec("CREATE INDEX IF NOT EXISTS idx_status ON daily_tasks (status)");
            } catch (Exception $e) {
                error_log('Index creation error (non-critical): ' . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log('ensureDailyTasksTable error: ' . $e->getMessage());
            
            // Fallback: create minimal table structure
            try {
                $fallbackSQL = "CREATE TABLE IF NOT EXISTS daily_tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    task_id INT NULL,
                    scheduled_date DATE NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    priority VARCHAR(20) DEFAULT 'medium',
                    status VARCHAR(50) DEFAULT 'not_started',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $db->exec($fallbackSQL);
                error_log('Created daily_tasks table with fallback structure');
            } catch (Exception $e2) {
                error_log('Fallback table creation also failed: ' . $e2->getMessage());
            }
        }
    }
    
    private function normalizeStatusValues($db) {
        try {
            // Map old status values to new ones
            $statusMappings = [
                'paused' => 'on_break',
                'break' => 'on_break',
                'pause' => 'on_break',
                'started' => 'in_progress',
                'active' => 'in_progress',
                'pending' => 'not_started',
                'assigned' => 'not_started',
                'done' => 'completed',
                'finished' => 'completed'
            ];
            
            foreach ($statusMappings as $oldStatus => $newStatus) {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE status = ?");
                $stmt->execute([$newStatus, $oldStatus]);
            }
            
            // Set any NULL or empty status to default
            $stmt = $db->prepare("UPDATE daily_tasks SET status = 'not_started' WHERE status IS NULL OR status = ''");
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log('Status normalization error (non-critical): ' . $e->getMessage());
        }
    }
    
    private function validateStatus($status) {
        $validStatuses = [
            'not_started',
            'in_progress', 
            'on_break',
            'completed',
            'postponed'
        ];
        
        // Normalize common variations
        $statusMappings = [
            'paused' => 'on_break',
            'break' => 'on_break',
            'pause' => 'on_break',
            'started' => 'in_progress',
            'active' => 'in_progress',
            'pending' => 'not_started',
            'assigned' => 'not_started',
            'done' => 'completed',
            'finished' => 'completed'
        ];
        
        $normalizedStatus = strtolower(trim($status));
        
        if (isset($statusMappings[$normalizedStatus])) {
            return $statusMappings[$normalizedStatus];
        }
        
        if (in_array($normalizedStatus, $validStatuses)) {
            return $normalizedStatus;
        }
        
        // Default fallback
        return 'not_started';
    }
    
    private function getNextWorkingDay() {
        $date = new DateTime('+1 day');
        while ($date->format('N') >= 6) { // Skip weekends
            $date->add(new DateInterval('P1D'));
        }
        return $date->format('Y-m-d');
    }
    
    public function getTaskHistory() {
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
            
            // Get postpone history
            $stmt = $db->prepare("
                SELECT 
                    DATE(updated_at) as date,
                    'Postponed' as action,
                    completed_percentage as progress,
                    postponed_from_date
                FROM daily_tasks 
                WHERE id = ? AND user_id = ? AND postponed_from_date IS NOT NULL
                ORDER BY updated_at DESC
                LIMIT 10
            ");
            $stmt->execute([$taskId, $_SESSION['user_id']]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            error_log('Get task history error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading history']);
        }
    }

}
?>