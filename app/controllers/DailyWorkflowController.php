<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DailyWorkflowController extends Controller {
    
    public function morningPlanner() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $userId = $_SESSION['user_id'];
            $today = date('Y-m-d');
            
            // Debug logging
            error_log("Morning Planner - User ID: $userId, Date: $today");
            
            // Create tables if they don't exist
            $this->ensureTables($db);
            
            // Get today's planned tasks with department names
            $stmt = $db->prepare("
                SELECT dt.*, d.name as department_name 
                FROM daily_tasks dt 
                LEFT JOIN departments d ON dt.department_id = d.id 
                WHERE dt.assigned_to = ? AND dt.planned_date = ? 
                ORDER BY dt.priority DESC, dt.created_at ASC
            ");
            $stmt->execute([$userId, $today]);
            $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log("Morning Planner - Found " . count($todayPlans) . " tasks for user $userId");
            
            // Check workflow status
            $stmt = $db->prepare("SELECT * FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$userId, $today]);
            $workflowStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Can submit if not submitted yet or if it's before 10 AM
            $canSubmit = !$workflowStatus || !$workflowStatus['morning_submitted_at'] || date('H') < 10;
            
            // Get departments for dropdown
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'todayPlans' => $todayPlans,
                'workflowStatus' => $workflowStatus,
                'canSubmit' => $canSubmit,
                'departments' => $departments,
                'active_page' => 'tasks',
                'debug_info' => [
                    'user_id' => $userId,
                    'today' => $today,
                    'task_count' => count($todayPlans),
                    'session_data' => $_SESSION
                ]
            ];
            
            $this->view('daily_workflow/morning_planner', $data);
        } catch (Exception $e) {
            error_log('Morning planner error: ' . $e->getMessage());
            $data = [
                'todayPlans' => [],
                'workflowStatus' => null,
                'canSubmit' => true,
                'active_page' => 'tasks',
                'error' => 'Unable to load planner data'
            ];
            $this->view('daily_workflow/morning_planner', $data);
        }
    }
    
    public function getProjectsByDepartment() {
        AuthMiddleware::requireAuth();
        
        $departmentId = $_GET['department_id'] ?? null;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM projects WHERE department_id = ? AND status = 'active' ORDER BY name");
            $stmt->execute([$departmentId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['projects' => $projects]);
        } catch (Exception $e) {
            echo json_encode(['projects' => [], 'error' => 'Failed to load projects']);
        }
    }
    
    public function getTaskCategoriesByDepartment() {
        AuthMiddleware::requireAuth();
        
        $departmentId = $_GET['department_id'] ?? null;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM task_categories WHERE department_id = ? ORDER BY name");
            $stmt->execute([$departmentId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['categories' => $categories]);
        } catch (Exception $e) {
            echo json_encode(['categories' => [], 'error' => 'Failed to load categories']);
        }
    }
    
    public function eveningUpdate() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTables($db);
            
            $userId = $_SESSION['user_id'];
            $today = date('Y-m-d');
            
            error_log("Evening Update - User ID: $userId, Date: $today");
            
            // Get today's planned tasks with all fields, ensuring defaults for missing columns
            $stmt = $db->prepare("
                SELECT 
                    id, title, description, priority, estimated_hours,
                    COALESCE(progress, 0) as progress,
                    COALESCE(status, 'planned') as status,
                    COALESCE(actual_hours, 0) as actual_hours,
                    COALESCE(completion_notes, '') as completion_notes,
                    created_at, updated_at
                FROM daily_tasks 
                WHERE assigned_to = ? AND planned_date = ? 
                ORDER BY priority DESC, created_at ASC
            ");
            $stmt->execute([$userId, $today]);
            $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Evening Update - Found " . count($todayPlans) . " tasks for user $userId");
            
            // Check if evening update is already done
            $stmt = $db->prepare("SELECT * FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$userId, $today]);
            $workflowStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get departments for dropdown
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Allow updates for testing - can be changed later
            $canUpdate = true; // !$workflowStatus || !$workflowStatus['evening_updated_at'];
            
            // Show success/error messages
            $message = '';
            if (isset($_GET['success'])) {
                $message = 'Evening update submitted successfully!';
            } elseif (isset($_GET['error'])) {
                $message = 'Error: ' . ($_GET['msg'] ?? 'Failed to submit evening update');
            }
            
            $data = [
                'todayPlans' => $todayPlans,
                'workflowStatus' => $workflowStatus,
                'canUpdate' => $canUpdate,
                'departments' => $departments,
                'active_page' => 'daily-workflow',
                'message' => $message,
                'debug_info' => [
                    'user_id' => $userId,
                    'today' => $today,
                    'task_count' => count($todayPlans),
                    'can_update' => $canUpdate
                ]
            ];
            
            $this->view('daily_workflow/evening_update', $data);
        } catch (Exception $e) {
            error_log('Evening update error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            $data = [
                'todayPlans' => [],
                'workflowStatus' => null,
                'canUpdate' => true,
                'active_page' => 'daily-workflow',
                'error' => 'Unable to load evening update data: ' . $e->getMessage()
            ];
            $this->view('daily_workflow/evening_update', $data);
        }
    }
    
    public function submitEveningUpdates() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensureTables($db);
                
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                error_log('Evening update submission - User: ' . $userId . ', Data: ' . print_r($_POST, true));
                
                $totalCompletedTasks = 0;
                $totalActualHours = 0;
                
                // Update planned tasks
                if (isset($_POST['updates']) && is_array($_POST['updates'])) {
                    foreach ($_POST['updates'] as $taskId => $update) {
                        $progress = intval($update['progress'] ?? 0);
                        $status = $update['status'] ?? 'pending';
                        $actualHours = floatval($update['actual_hours'] ?? 0);
                        $completionNotes = trim($update['completion_notes'] ?? '');
                        
                        $stmt = $db->prepare("UPDATE daily_tasks SET progress = ?, status = ?, actual_hours = ?, completion_notes = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?");
                        $result = $stmt->execute([
                            $progress,
                            $status,
                            $actualHours,
                            $completionNotes,
                            $taskId,
                            $userId
                        ]);
                        
                        if ($result) {
                            if ($status === 'completed') {
                                $totalCompletedTasks++;
                            }
                            $totalActualHours += $actualHours;
                            error_log("Updated task $taskId: progress=$progress, status=$status, hours=$actualHours");
                        }
                    }
                }
                
                // Add unplanned tasks
                if (isset($_POST['unplanned_tasks']) && is_array($_POST['unplanned_tasks'])) {
                    foreach ($_POST['unplanned_tasks'] as $task) {
                        if (!empty(trim($task['title'] ?? ''))) {
                            $title = trim($task['title']);
                            $description = trim($task['description'] ?? '');
                            $status = $task['status'] ?? 'completed';
                            $actualHours = floatval($task['actual_hours'] ?? 0);
                            $progress = $status === 'completed' ? 100 : ($status === 'in_progress' ? 50 : 0);
                            
                            $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, status, actual_hours, progress, department_id, task_category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                            $result = $stmt->execute([
                                $title,
                                $description,
                                $userId,
                                $today,
                                $status,
                                $actualHours,
                                $progress,
                                !empty($task['department_id']) ? intval($task['department_id']) : null,
                                !empty($task['task_category']) ? trim($task['task_category']) : null
                            ]);
                            
                            if ($result) {
                                if ($status === 'completed') {
                                    $totalCompletedTasks++;
                                }
                                $totalActualHours += $actualHours;
                                error_log("Added unplanned task: $title, status=$status, hours=$actualHours");
                            }
                        }
                    }
                }
                
                // Calculate productivity score
                $stmt = $db->prepare("SELECT COUNT(*) as total_tasks, SUM(estimated_hours) as total_estimated FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
                $stmt->execute([$userId, $today]);
                $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $productivityScore = 0;
                if ($taskStats['total_estimated'] > 0) {
                    $productivityScore = min(100, ($totalActualHours / $taskStats['total_estimated']) * 100);
                }
                
                // Update workflow status with calculated metrics
                $stmt = $db->prepare("
                    INSERT INTO daily_workflow_status 
                    (user_id, workflow_date, evening_updated_at, total_completed_tasks, total_actual_hours, productivity_score) 
                    VALUES (?, ?, NOW(), ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    evening_updated_at = NOW(), 
                    total_completed_tasks = ?, 
                    total_actual_hours = ?, 
                    productivity_score = ?
                ");
                $stmt->execute([
                    $userId, $today, $totalCompletedTasks, $totalActualHours, $productivityScore,
                    $totalCompletedTasks, $totalActualHours, $productivityScore
                ]);
                
                error_log("Evening update completed - Tasks: $totalCompletedTasks, Hours: $totalActualHours, Score: $productivityScore");
                
                // Notify owners about evening update submission
                require_once __DIR__ . '/../helpers/NotificationHelper.php';
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    NotificationHelper::notifyOwners(
                        $userId,
                        'evening_update',
                        'submitted',
                        "{$user['name']} submitted evening update with {$productivityScore}% productivity",
                        null
                    );
                }
                
                // Determine redirect URL based on current request path
                $redirectUrl = strpos($_SERVER['REQUEST_URI'], '/evening-update') !== false ? 
                    '/ergon/evening-update?success=1&t=' . time() : 
                    '/ergon/daily-workflow/evening-update?success=1&t=' . time();
                header('Location: ' . $redirectUrl);
                exit;
            } catch (Exception $e) {
                error_log('Submit evening updates error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
                // Determine redirect URL based on current request path
                $redirectUrl = strpos($_SERVER['REQUEST_URI'], '/evening-update') !== false ? 
                    '/ergon/evening-update?error=1&msg=' . urlencode($e->getMessage()) : 
                    '/ergon/daily-workflow/evening-update?error=1&msg=' . urlencode($e->getMessage());
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }
    
    private function createPlannerFollowup($db, $taskId, $plan, $userId) {
        try {
            $followupDate = date('Y-m-d', strtotime('+1 day'));
            
            $stmt = $db->prepare("INSERT INTO followups (user_id, task_id, title, description, company_name, contact_person, contact_phone, follow_up_date, original_date, status, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
            $stmt->execute([
                $userId,
                $taskId,
                'Follow-up: ' . $plan['title'],
                'Auto-created from daily planner: ' . ($plan['description'] ?? ''),
                $plan['company_name'] ?? '',
                $plan['contact_person'] ?? '',
                $plan['contact_phone'] ?? '',
                $followupDate,
                $followupDate,
                $plan['priority'] ?? 'medium'
            ]);
            
            error_log('Auto-followup created from planner for task ID: ' . $taskId);
        } catch (Exception $e) {
            error_log('Planner auto-followup creation failed: ' . $e->getMessage());
        }
    }
    
    private function ensureTables($db) {
        try {
            // Create daily_tasks table
            $db->exec("CREATE TABLE IF NOT EXISTS daily_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                assigned_to INT NOT NULL,
                planned_date DATE NOT NULL,
                priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
                estimated_hours DECIMAL(4,2) DEFAULT 1.00,
                actual_hours DECIMAL(4,2) DEFAULT 0.00,
                status ENUM('planned','pending','in_progress','completed','cancelled','blocked') DEFAULT 'planned',
                progress INT DEFAULT 0,
                completion_notes TEXT,
                department_id INT DEFAULT NULL,
                task_category VARCHAR(100) DEFAULT NULL,
                company_name VARCHAR(255) DEFAULT NULL,
                contact_person VARCHAR(255) DEFAULT NULL,
                contact_phone VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_assigned_date (assigned_to, planned_date),
                INDEX idx_status (status)
            )");
            
            // Add new columns if they don't exist (for existing installations)
            $columnsToAdd = [
                'task_category' => 'ALTER TABLE daily_tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL',
                'company_name' => 'ALTER TABLE daily_tasks ADD COLUMN company_name VARCHAR(255) DEFAULT NULL',
                'contact_person' => 'ALTER TABLE daily_tasks ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL',
                'contact_phone' => 'ALTER TABLE daily_tasks ADD COLUMN contact_phone VARCHAR(20) DEFAULT NULL',
                'progress' => 'ALTER TABLE daily_tasks ADD COLUMN progress INT DEFAULT 0',
                'actual_hours' => 'ALTER TABLE daily_tasks ADD COLUMN actual_hours DECIMAL(4,2) DEFAULT 0.00',
                'completion_notes' => 'ALTER TABLE daily_tasks ADD COLUMN completion_notes TEXT DEFAULT NULL'
            ];
            
            foreach ($columnsToAdd as $column => $sql) {
                try {
                    $db->exec($sql);
                } catch (Exception $e) {
                    // Column already exists
                }
            }
            
            // Update status enum to include all needed values
            try {
                $db->exec("ALTER TABLE daily_tasks MODIFY COLUMN status ENUM('planned','pending','in_progress','completed','cancelled','blocked') DEFAULT 'planned'");
            } catch (Exception $e) {
                // Status enum already updated
            }
            
            // Create daily_workflow_status table
            $db->exec("CREATE TABLE IF NOT EXISTS daily_workflow_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                workflow_date DATE NOT NULL,
                morning_submitted_at TIMESTAMP NULL,
                evening_updated_at TIMESTAMP NULL,
                total_completed_tasks INT DEFAULT 0,
                total_actual_hours DECIMAL(6,2) DEFAULT 0.00,
                productivity_score DECIMAL(5,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_date (user_id, workflow_date)
            )");
            
            // Add workflow status columns if they don't exist
            $workflowColumns = [
                'total_completed_tasks' => 'ALTER TABLE daily_workflow_status ADD COLUMN total_completed_tasks INT DEFAULT 0',
                'total_actual_hours' => 'ALTER TABLE daily_workflow_status ADD COLUMN total_actual_hours DECIMAL(6,2) DEFAULT 0.00',
                'productivity_score' => 'ALTER TABLE daily_workflow_status ADD COLUMN productivity_score DECIMAL(5,2) DEFAULT 0.00'
            ];
            
            foreach ($workflowColumns as $column => $sql) {
                try {
                    $db->exec($sql);
                } catch (Exception $e) {
                    // Column already exists
                }
            }
        } catch (Exception $e) {
            error_log('Table creation error: ' . $e->getMessage());
        }
    }
    
    public function submitMorningPlans() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensureTables($db);
                
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                // Debug POST data
                error_log('POST data received: ' . print_r($_POST, true));
                
                // Process submitted plans
                if (isset($_POST['plans']) && is_array($_POST['plans'])) {
                    error_log('Processing ' . count($_POST['plans']) . ' plans');
                    foreach ($_POST['plans'] as $index => $plan) {
                        error_log('Processing plan ' . $index . ': ' . print_r($plan, true));
                        if (!empty($plan['title'])) {
                            $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, department_id, task_category, company_name, contact_person, contact_phone, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', ?, ?, ?, ?, ?, NOW())");
                            $result = $stmt->execute([
                                trim($plan['title']),
                                trim($plan['description'] ?? ''),
                                $userId,
                                $today,
                                $plan['priority'] ?? 'medium',
                                floatval($plan['estimated_hours'] ?? 1),
                                !empty($plan['department_id']) ? intval($plan['department_id']) : null,
                                !empty($plan['task_category']) ? trim($plan['task_category']) : null,
                                !empty($plan['company_name']) ? trim($plan['company_name']) : null,
                                !empty($plan['contact_person']) ? trim($plan['contact_person']) : null,
                                !empty($plan['contact_phone']) ? trim($plan['contact_phone']) : null
                            ]);
                            
                            if ($result) {
                                $taskId = $db->lastInsertId();
                                
                                // Auto-create followup if category contains "follow"
                                if (!empty($plan['task_category']) && stripos($plan['task_category'], 'follow') !== false) {
                                    $this->createPlannerFollowup($db, $taskId, $plan, $userId);
                                }
                            }
                        }
                    }
                }
                
                // Update workflow status
                $stmt = $db->prepare("INSERT INTO daily_workflow_status (user_id, workflow_date, morning_submitted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE morning_submitted_at = NOW()");
                $stmt->execute([$userId, $today]);
                
                // Notify owners about daily plan submission
                require_once __DIR__ . '/../helpers/NotificationHelper.php';
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    NotificationHelper::notifyOwners(
                        $userId,
                        'planner',
                        'submitted',
                        "{$user['name']} submitted daily plan with " . count($_POST['plans']) . " tasks",
                        null
                    );
                }
                
                // Always redirect to show updated tasks (no AJAX for form submission)
                // Determine redirect URL based on current request path
                $redirectUrl = strpos($_SERVER['REQUEST_URI'], '/planner') !== false ? 
                    '/ergon/planner?success=1&t=' . time() : 
                    '/ergon/daily-workflow/morning-planner?success=1&t=' . time();
                header('Location: ' . $redirectUrl);
                exit;
            } catch (Exception $e) {
                error_log('Submit morning plans error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
                error_log('POST data: ' . print_r($_POST, true));
                error_log('User ID: ' . ($userId ?? 'not set'));
                error_log('Today: ' . ($today ?? 'not set'));
                
                // Determine redirect URL based on current request path
                $redirectUrl = strpos($_SERVER['REQUEST_URI'], '/planner') !== false ? 
                    '/ergon/planner?error=1&msg=' . urlencode($e->getMessage()) : 
                    '/ergon/daily-workflow/morning-planner?error=1&msg=' . urlencode($e->getMessage());
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }
    
    public function addTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensureTables($db);
                
                $taskData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'assigned_to' => $_SESSION['user_id'],
                    'planned_date' => $_POST['planned_date'] ?? date('Y-m-d'),
                    'priority' => $_POST['priority'] ?? 'medium',
                    'estimated_hours' => floatval($_POST['estimated_hours'] ?? 1),
                    'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                    'task_category' => !empty($_POST['task_category']) ? trim($_POST['task_category']) : null,
                    'company_name' => !empty($_POST['company_name']) ? trim($_POST['company_name']) : null,
                    'contact_person' => !empty($_POST['contact_person']) ? trim($_POST['contact_person']) : null,
                    'contact_phone' => !empty($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null
                ];
                
                $sql = "INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, department_id, task_category, company_name, contact_person, contact_phone, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $taskData['title'],
                    $taskData['description'],
                    $taskData['assigned_to'],
                    $taskData['planned_date'],
                    $taskData['priority'],
                    $taskData['estimated_hours'],
                    $taskData['department_id'],
                    $taskData['task_category'],
                    $taskData['company_name'],
                    $taskData['contact_person'],
                    $taskData['contact_phone']
                ]);
                
                echo json_encode(['success' => $result, 'task_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                error_log('Add task error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to add task']);
            }
        }
    }
    
    public function updateTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $taskId = $_POST['task_id'] ?? null;
                $userId = $_SESSION['user_id'];
                
                if (!$taskId) {
                    echo json_encode(['success' => false, 'error' => 'Task ID required']);
                    return;
                }
                
                $updateFields = [];
                $params = [];
                
                if (isset($_POST['title'])) {
                    $updateFields[] = 'title = ?';
                    $params[] = trim($_POST['title']);
                }
                
                if (isset($_POST['description'])) {
                    $updateFields[] = 'description = ?';
                    $params[] = trim($_POST['description']);
                }
                
                if (isset($_POST['priority'])) {
                    $updateFields[] = 'priority = ?';
                    $params[] = $_POST['priority'];
                }
                
                if (isset($_POST['estimated_hours'])) {
                    $updateFields[] = 'estimated_hours = ?';
                    $params[] = floatval($_POST['estimated_hours']);
                }
                
                if (isset($_POST['department_id'])) {
                    $updateFields[] = 'department_id = ?';
                    $params[] = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
                }
                
                if (isset($_POST['task_category'])) {
                    $updateFields[] = 'task_category = ?';
                    $params[] = !empty($_POST['task_category']) ? trim($_POST['task_category']) : null;
                }
                
                if (isset($_POST['status'])) {
                    $updateFields[] = 'status = ?';
                    $params[] = $_POST['status'];
                }
                
                if (isset($_POST['progress'])) {
                    $updateFields[] = 'progress = ?';
                    $params[] = intval($_POST['progress']);
                }
                
                if (isset($_POST['company_name'])) {
                    $updateFields[] = 'company_name = ?';
                    $params[] = !empty($_POST['company_name']) ? trim($_POST['company_name']) : null;
                }
                
                if (isset($_POST['contact_person'])) {
                    $updateFields[] = 'contact_person = ?';
                    $params[] = !empty($_POST['contact_person']) ? trim($_POST['contact_person']) : null;
                }
                
                if (isset($_POST['contact_phone'])) {
                    $updateFields[] = 'contact_phone = ?';
                    $params[] = !empty($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null;
                }
                
                if (empty($updateFields)) {
                    echo json_encode(['success' => false, 'error' => 'No fields to update']);
                    return;
                }
                
                $updateFields[] = 'updated_at = NOW()';
                $params[] = $taskId;
                $params[] = $userId;
                
                $sql = "UPDATE daily_tasks SET " . implode(', ', $updateFields) . " WHERE id = ? AND assigned_to = ?";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute($params);
                
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                error_log('Update task error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to update task']);
            }
        }
    }
    
    public function deleteTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $taskId = $_POST['task_id'] ?? null;
                $userId = $_SESSION['user_id'];
                
                if (!$taskId) {
                    echo json_encode(['success' => false, 'error' => 'Task ID required']);
                    return;
                }
                
                $stmt = $db->prepare("DELETE FROM daily_tasks WHERE id = ? AND assigned_to = ?");
                $result = $stmt->execute([$taskId, $userId]);
                
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                error_log('Delete task error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
            }
        }
    }
    
    public function getTasks() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $userId = $_SESSION['user_id'];
            $date = $_GET['date'] ?? date('Y-m-d');
            
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY priority DESC, created_at ASC");
            $stmt->execute([$userId, $date]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
        } catch (Exception $e) {
            error_log('Get tasks error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to get tasks']);
        }
    }
    
    public function getTask() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $taskId = $_GET['id'] ?? null;
            $userId = $_SESSION['user_id'];
            
            if (!$taskId) {
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                return;
            }
            
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE id = ? AND assigned_to = ?");
            $stmt->execute([$taskId, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                echo json_encode(['success' => true, 'task' => $task]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
            }
        } catch (Exception $e) {
            error_log('Get task error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to get task']);
        }
    }
}
?>