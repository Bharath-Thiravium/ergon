<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UnifiedWorkflowController extends Controller {
    

    
    public function dailyPlanner($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        // Restrict to current and past dates only
        if ($date > date('Y-m-d')) {
            header('Location: /ergon/workflow/daily-planner/' . date('Y-m-d'));
            exit;
        }
        
        $currentUserId = $_SESSION['user_id'];
        
        // Only carry forward for current date or future dates
        $shouldCarryForward = $date >= date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Auto-rollover uncompleted tasks when accessing today's planner
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            
            if ($date === date('Y-m-d')) {
                $rolledCount = $planner->rolloverUncompletedTasks();
                if ($rolledCount > 0) {
                    error_log("Rolled over {$rolledCount} tasks for user {$currentUserId}");
                }
            }
            
            // Ensure daily_tasks table exists
            $this->ensureDailyTasksTable($db);
            
            // Get existing daily tasks for current user and date
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
            $stmt->execute([$currentUserId, $date]);
            $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create daily tasks if none exist
            if (empty($dailyTasks)) {
                $this->ensureDailyTasksExist($db, $currentUserId, $date);
                
                // Re-fetch after creation
                $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                $stmt->execute([$currentUserId, $date]);
                $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Stable refresh - only sync new tasks without deleting existing ones
            if (isset($_GET['refresh']) && $_GET['refresh'] === '1') {
                $syncedCount = $this->syncNewTasksOnly($db, $currentUserId, $date);
                
                // Store sync result for display
                $_SESSION['sync_message'] = $syncedCount > 0 
                    ? "Added {$syncedCount} new task(s) from Tasks module"
                    : "No new tasks to sync";
                
                // Re-fetch after sync
                $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
                $stmt->execute([$currentUserId, $date]);
                $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Use DailyPlanner model for both tasks and stats
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            $plannedTasks = $planner->getTasksForDate($currentUserId, $date);
            $dailyStats = $planner->getDailyStats($currentUserId, $date);
            
            $this->view('daily_workflow/unified_daily_planner', [
                'planned_tasks' => $plannedTasks,
                'daily_stats' => $dailyStats,
                'selected_date' => $date,
                'current_user_id' => $currentUserId,
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
            
            // Get followups from followups table including task-linked ones
            try {
                $stmt = $db->prepare("
                    SELECT f.*, 
                           t.title as task_title, 
                           t.assigned_to,
                           u.name as assigned_user,
                           c.name as contact_name,
                           c.company as contact_company
                    FROM followups f 
                    LEFT JOIN tasks t ON f.task_id = t.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN contacts c ON f.contact_id = c.id
                    WHERE (f.followup_type = 'standalone' OR (f.followup_type = 'task' AND t.assigned_to = ?) OR f.task_id IS NOT NULL)
                    ORDER BY f.follow_up_date ASC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug logging
                error_log('Followups query executed for user: ' . $_SESSION['user_id']);
                error_log('Found followups: ' . count($followups));
                if (!empty($followups)) {
                    error_log('First followup: ' . json_encode($followups[0]));
                }
            } catch (Exception $e) {
                error_log('Followups complex query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("SELECT * FROM followups ORDER BY follow_up_date ASC");
                $stmt->execute();
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Fallback query found: ' . count($followups) . ' followups');
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
            
            // Ensure table exists with all required columns
            $this->ensureDailyTasksTable($db);
            
            $now = date('Y-m-d H:i:s');
            
            // Get task and SLA info
            $stmt = $db->prepare("SELECT dt.*, COALESCE(t.sla_hours, 1) as sla_hours FROM daily_tasks dt LEFT JOIN tasks t ON dt.task_id = t.id WHERE dt.id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            if ($task['status'] !== 'not_started') {
                echo json_encode(['success' => false, 'message' => 'Task already started']);
                return;
            }
            
            // Calculate SLA end time
            $slaEndTime = date('Y-m-d H:i:s', strtotime($now . ' +' . $task['sla_hours'] . ' hours'));
            
            // Start the task with full SLA support
            $stmt = $db->prepare("UPDATE daily_tasks SET status = 'in_progress', start_time = ?, sla_end_time = ?, resume_time = NULL WHERE id = ?");
            $result = $stmt->execute([$now, $slaEndTime, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Log SLA history
                try {
                    $stmt = $db->prepare("INSERT INTO sla_history (daily_task_id, action, timestamp, notes) VALUES (?, 'start', ?, 'Task started')");
                    $stmt->execute([$taskId, $now]);
                } catch (Exception $e) {
                    error_log('SLA history log error: ' . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Task started successfully',
                    'task_id' => $taskId,
                    'status' => 'in_progress',
                    'start_time' => $now,
                    'sla_end_time' => $slaEndTime
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to start task']);
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
            
            try {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, pause_time = NOW() WHERE id = ?");
                $result = $stmt->execute([$status, $taskId]);
            } catch (Exception $e) {
                // Fallback for missing columns
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE id = ?");
                $result = $stmt->execute([$status, $taskId]);
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
            
            try {
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ?, resume_time = NOW() WHERE id = ?");
                $result = $stmt->execute([$status, $taskId]);
            } catch (Exception $e) {
                // Fallback for missing columns
                $stmt = $db->prepare("UPDATE daily_tasks SET status = ? WHERE id = ?");
                $result = $stmt->execute([$status, $taskId]);
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
            // Create table with all required columns for SLA functionality
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
                sla_end_time TIMESTAMP NULL,
                active_seconds INT DEFAULT 0,
                total_pause_duration INT DEFAULT 0,
                completed_percentage INT DEFAULT 0,
                postponed_from_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $db->exec($createSQL);
            
            // Add missing columns if table already exists
            $this->addMissingColumns($db);
            
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
            
            // Ensure SLA history table exists
            $this->ensureSLAHistoryTable($db);
            
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
    
    private function createDailyTasksFromRegular($db, $userId, $date, $existingStatuses = []) {
        try {
            // Ensure tasks have SLA hours
            $db->exec("UPDATE tasks SET sla_hours = 1.0 WHERE sla_hours IS NULL OR sla_hours = 0");
            
            // Get tasks for this user that are planned for the specific date
            // Carry forward is now handled separately before this method is called
            $stmt = $db->prepare("
                SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks 
                WHERE assigned_to = ? 
                AND status != 'completed'
                AND (
                    planned_date = ? OR 
                    (planned_date IS NULL AND DATE(created_at) = ?)
                )
                ORDER BY 
                    CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                    CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                    created_at DESC
            ");
            $stmt->execute([$userId, $date, $date]);
            $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($regularTasks as $task) {
                $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
                    ? "[From Others] " . $task['title']
                    : "[Self] " . $task['title'];
                
                $plannedDuration = floatval($task['sla_hours']) * 60;
                
                // Check if we have existing status for this task
                $existingStatus = $existingStatuses[$task['id']] ?? null;
                $status = $existingStatus ? $existingStatus['status'] : 'not_started';
                $startTime = $existingStatus ? $existingStatus['start_time'] : null;
                $activeSeconds = $existingStatus ? $existingStatus['active_seconds'] : 0;
                $completedPercentage = $existingStatus ? $existingStatus['completed_percentage'] : 0;
                
                // Use the planned_date from the task, or fall back to the current date
                $taskScheduledDate = $task['planned_date'] ?? $date;
                
                $stmt = $db->prepare("
                    INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, start_time, active_seconds, total_pause_duration, completed_percentage, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
                ");
                $stmt->execute([
                    $userId, $task['id'], $taskScheduledDate, $taskTitle, 
                    $task['description'], $plannedDuration, $task['priority'] ?? 'medium',
                    $status, $startTime, $activeSeconds, $completedPercentage
                ]);
            }
        } catch (Exception $e) {
            error_log('Create daily tasks error: ' . $e->getMessage());
            error_log('Query was for user: ' . $userId . ', date: ' . $date);
        }
    }
    
    private function carryForwardPendingTasks($db, $userId, $currentDate) {
        try {
            // Find unattended/pending tasks from previous dates
            $stmt = $db->prepare("
                UPDATE tasks SET planned_date = ? 
                WHERE assigned_to = ? 
                AND status IN ('assigned', 'not_started') 
                AND planned_date < ? 
                AND planned_date IS NOT NULL
            ");
            $result = $stmt->execute([$currentDate, $userId, $currentDate]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Carried forward {$stmt->rowCount()} pending tasks to {$currentDate} for user {$userId}");
                return $stmt->rowCount();
            }
            return 0;
        } catch (Exception $e) {
            error_log('Carry forward pending tasks error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function manualCarryForward() {
        AuthMiddleware::requireAuth();
        header('Content-Type: application/json');
        
        // Only allow manual carry forward for testing/admin purposes
        if (($_SESSION['role'] ?? 'user') !== 'owner') {
            echo json_encode([
                'success' => false,
                'message' => 'Manual carry forward is only available for testing. Use the daily cron job instead.'
            ]);
            return;
        }
        
        try {
            // Run the daily carry forward script
            ob_start();
            include __DIR__ . '/../../cron/daily_carry_forward.php';
            $output = ob_get_clean();
            
            echo json_encode([
                'success' => true,
                'message' => 'Manual carry forward completed',
                'output' => $output
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function createDailyTasksFromRegularWithoutCarryForward($db, $userId, $date, $existingStatuses = []) {
        try {
            // Ensure tasks have SLA hours
            $db->exec("UPDATE tasks SET sla_hours = 1.0 WHERE sla_hours IS NULL OR sla_hours = 0");
            
            // Get tasks for this user that are planned for the specific date (no carry forward)
            $stmt = $db->prepare("
                SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks 
                WHERE assigned_to = ? 
                AND status != 'completed'
                AND (
                    planned_date = ? OR 
                    (planned_date IS NULL AND DATE(created_at) = ?)
                )
                ORDER BY 
                    CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                    CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                    created_at DESC
            ");
            $stmt->execute([$userId, $date, $date]);
            $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($regularTasks as $task) {
                $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
                    ? "[From Others] " . $task['title']
                    : "[Self] " . $task['title'];
                
                $plannedDuration = floatval($task['sla_hours']) * 60;
                
                // Check if we have existing status for this task
                $existingStatus = $existingStatuses[$task['id']] ?? null;
                $status = $existingStatus ? $existingStatus['status'] : 'not_started';
                $startTime = $existingStatus ? $existingStatus['start_time'] : null;
                $activeSeconds = $existingStatus ? $existingStatus['active_seconds'] : 0;
                $completedPercentage = $existingStatus ? $existingStatus['completed_percentage'] : 0;
                
                // Use the planned_date from the task, or fall back to the current date
                $taskScheduledDate = $task['planned_date'] ?? $date;
                
                $stmt = $db->prepare("
                    INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, start_time, active_seconds, total_pause_duration, completed_percentage, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
                ");
                $stmt->execute([
                    $userId, $task['id'], $taskScheduledDate, $taskTitle, 
                    $task['description'], $plannedDuration, $task['priority'] ?? 'medium',
                    $status, $startTime, $activeSeconds, $completedPercentage
                ]);
            }
        } catch (Exception $e) {
            error_log('Create daily tasks without carry forward error: ' . $e->getMessage());
            error_log('Query was for user: ' . $userId . ', date: ' . $date);
        }
    }
    
    private function processDailyTasks($db, $dailyTasks) {
        $plannedTasks = [];
        
        foreach ($dailyTasks as $task) {
            $slaHours = 1;
            if (!empty($task['task_id'])) {
                try {
                    $stmt = $db->prepare("SELECT sla_hours FROM tasks WHERE id = ?");
                    $stmt->execute([$task['task_id']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && !empty($result['sla_hours'])) {
                        $slaHours = (float)$result['sla_hours'];
                    }
                } catch (Exception $e) {
                    error_log('SLA fetch error: ' . $e->getMessage());
                }
            }
            
            $plannedTasks[] = [
                'id' => $task['id'],
                'task_id' => $task['task_id'] ?? null,
                'title' => $task['title'] ?? 'Untitled Task',
                'description' => $task['description'] ?? '',
                'priority' => $task['priority'] ?? 'medium',
                'status' => $task['status'] ?? 'not_started',
                'sla_hours' => $slaHours,
                'start_time' => $task['start_time'] ?? null,
                'pause_time' => $task['pause_time'] ?? null,
                'resume_time' => $task['resume_time'] ?? null,
                'active_seconds' => $task['active_seconds'] ?? 0,
                'pause_duration' => $task['total_pause_duration'] ?? 0,
                'planned_duration' => $task['planned_duration'] ?? 60,
                'completed_percentage' => $task['completed_percentage'] ?? 0
            ];
        }
        
        return $plannedTasks;
    }
    
    private function calculateDailyStats($plannedTasks) {
        return [
            'total_tasks' => count($plannedTasks),
            'completed_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'completed')),
            'in_progress_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'in_progress')),
            'postponed_tasks' => count(array_filter($plannedTasks, fn($t) => $t['status'] === 'postponed')),
            'total_planned_minutes' => array_sum(array_map(fn($t) => ($t['sla_hours'] ?? 1) * 60, $plannedTasks)),
            'total_active_seconds' => 0,
            'avg_completion' => 0
        ];
    }
    
    private function addMissingColumns($db) {
        try {
            // Check and add missing columns
            $columnsToAdd = [
                'sla_end_time' => 'TIMESTAMP NULL',
                'total_pause_duration' => 'INT DEFAULT 0'
            ];
            
            foreach ($columnsToAdd as $column => $definition) {
                try {
                    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE ?");
                    $stmt->execute([$column]);
                    if (!$stmt->fetch()) {
                        $db->exec("ALTER TABLE daily_tasks ADD COLUMN {$column} {$definition}");
                        error_log("Added missing column: {$column}");
                    }
                } catch (Exception $e) {
                    error_log("Error adding column {$column}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log('Add missing columns error: ' . $e->getMessage());
        }
    }
    
    private function ensureSLAHistoryTable($db) {
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS sla_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    daily_task_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    timestamp TIMESTAMP NOT NULL,
                    duration_seconds INT DEFAULT 0,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_daily_task_id (daily_task_id)
                )
            ");
        } catch (Exception $e) {
            error_log('SLA history table creation error: ' . $e->getMessage());
        }
    }
    
    private function getNextWorkingDay() {
        $date = new DateTime('+1 day');
        while ($date->format('N') >= 6) { // Skip weekends
            $date->add(new DateInterval('P1D'));
        }
        return $date->format('Y-m-d');
    }
    
    private function ensureDailyTasksExist($db, $userId, $date) {
        try {
            $isCurrentDate = ($date === date('Y-m-d'));
            
            if ($isCurrentDate) {
                // Current date: include rollover tasks from previous days
                $stmt = $db->prepare("
                    SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks t
                    WHERE assigned_to = ? 
                    AND status != 'completed'
                    AND (
                        planned_date = ? OR 
                        DATE(deadline) = ? OR
                        (planned_date IS NULL AND DATE(created_at) = ?) OR
                        (status = 'in_progress') OR
                        (planned_date < ? AND status IN ('assigned', 'not_started', 'in_progress'))
                    )
                    ORDER BY 
                        CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                        CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
                ");
                $stmt->execute([$userId, $date, $date, $date, $date]);
            } else {
                // Past date: only tasks specific to that date + completed on that date
                $stmt = $db->prepare("
                    SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks t
                    WHERE assigned_to = ? 
                    AND (
                        planned_date = ? OR 
                        DATE(deadline) = ? OR
                        (planned_date IS NULL AND DATE(created_at) = ?) OR
                        (status = 'completed' AND DATE(updated_at) = ?)
                    )
                    ORDER BY 
                        CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                        CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
                ");
                $stmt->execute([$userId, $date, $date, $date, $date]);
            }
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create daily tasks
            foreach ($tasks as $task) {
                // Check if already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND task_id = ? AND scheduled_date = ?");
                $stmt->execute([$userId, $task['id'], $date]);
                
                if ($stmt->fetchColumn() == 0) {
                    $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
                        ? "[From Others] " . $task['title']
                        : "[Self] " . $task['title'];
                    
                    $plannedDuration = floatval($task['sla_hours']) * 60;
                    
                    $stmt = $db->prepare("
                        INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
                    ");
                    $stmt->execute([
                        $userId, $task['id'], $date, $taskTitle, 
                        $task['description'], $plannedDuration, $task['priority'] ?? 'medium'
                    ]);
                }
            }
            
            return count($tasks);
        } catch (Exception $e) {
            error_log('Ensure daily tasks exist error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function syncNewTasksOnly($db, $userId, $date) {
        try {
            $isCurrentDate = ($date === date('Y-m-d'));
            
            // Get existing daily task IDs
            $stmt = $db->prepare("SELECT task_id FROM daily_tasks WHERE user_id = ? AND scheduled_date = ? AND task_id IS NOT NULL");
            $stmt->execute([$userId, $date]);
            $existingTaskIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'task_id');
            
            // Get new tasks from tasks table that aren't in daily_tasks yet
            $placeholders = $existingTaskIds ? str_repeat('?,', count($existingTaskIds) - 1) . '?' : '';
            $excludeClause = $existingTaskIds ? "AND t.id NOT IN ($placeholders)" : '';
            
            if ($isCurrentDate) {
                // Current date: include rollover tasks
                $stmt = $db->prepare("
                    SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks t
                    WHERE assigned_to = ? 
                    AND status != 'completed'
                    AND (
                        planned_date = ? OR 
                        DATE(deadline) = ? OR
                        (planned_date IS NULL AND DATE(created_at) = ?) OR
                        (planned_date < ? AND status IN ('assigned', 'not_started', 'in_progress'))
                    )
                    $excludeClause
                    ORDER BY 
                        CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                        CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
                ");
                $params = [$userId, $date, $date, $date, $date];
            } else {
                // Past date: only date-specific tasks + completed on that date
                $stmt = $db->prepare("
                    SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks t
                    WHERE assigned_to = ? 
                    AND (
                        planned_date = ? OR 
                        DATE(deadline) = ? OR
                        (planned_date IS NULL AND DATE(created_at) = ?) OR
                        (status = 'completed' AND DATE(updated_at) = ?)
                    )
                    $excludeClause
                    ORDER BY 
                        CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
                        CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
                ");
                $params = [$userId, $date, $date, $date, $date];
            }
            
            if ($existingTaskIds) {
                $params = array_merge($params, $existingTaskIds);
            }
            
            $stmt->execute($params);
            $newTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add only new tasks
            foreach ($newTasks as $task) {
                $taskTitle = ($task['assigned_by'] != $task['assigned_to']) 
                    ? "[From Others] " . $task['title']
                    : "[Self] " . $task['title'];
                
                $plannedDuration = floatval($task['sla_hours']) * 60;
                
                $stmt = $db->prepare("
                    INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
                ");
                $stmt->execute([
                    $userId, $task['id'], $date, $taskTitle, 
                    $task['description'], $plannedDuration, $task['priority'] ?? 'medium'
                ]);
            }
            
            return count($newTasks);
        } catch (Exception $e) {
            error_log('Sync new tasks error: ' . $e->getMessage());
            return 0;
        }
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