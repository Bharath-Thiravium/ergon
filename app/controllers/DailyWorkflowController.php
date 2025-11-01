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
            
            // Create tables if they don't exist
            $this->ensureTables($db);
            
            // Get today's planned tasks
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY priority DESC, created_at ASC");
            $stmt->execute([$userId, $today]);
            $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check workflow status
            $stmt = $db->prepare("SELECT * FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$userId, $today]);
            $workflowStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Can submit if not submitted yet or if it's before 10 AM
            $canSubmit = !$workflowStatus || !$workflowStatus['morning_submitted_at'] || date('H') < 10;
            
            $data = [
                'todayPlans' => $todayPlans,
                'workflowStatus' => $workflowStatus,
                'canSubmit' => $canSubmit,
                'active_page' => 'tasks'
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
            
            // Get today's planned tasks
            $stmt = $db->prepare("SELECT * FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY priority DESC, created_at ASC");
            $stmt->execute([$userId, $today]);
            $todayPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if evening update is already done
            $stmt = $db->prepare("SELECT * FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$userId, $today]);
            $workflowStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $canUpdate = !$workflowStatus || !$workflowStatus['evening_updated_at'];
            
            $data = [
                'todayPlans' => $todayPlans,
                'workflowStatus' => $workflowStatus,
                'canUpdate' => $canUpdate,
                'active_page' => 'daily-workflow'
            ];
            
            $this->view('daily_workflow/evening_update', $data);
        } catch (Exception $e) {
            error_log('Evening update error: ' . $e->getMessage());
            $data = [
                'todayPlans' => [],
                'workflowStatus' => null,
                'canUpdate' => true,
                'active_page' => 'daily-workflow',
                'error' => 'Unable to load evening update data'
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
                
                $userId = $_SESSION['user_id'];
                $today = date('Y-m-d');
                
                // Update planned tasks
                if (isset($_POST['updates'])) {
                    foreach ($_POST['updates'] as $taskId => $update) {
                        $stmt = $db->prepare("UPDATE daily_tasks SET progress = ?, status = ?, actual_hours = ?, completion_notes = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?");
                        $stmt->execute([
                            intval($update['progress'] ?? 0),
                            $update['status'] ?? 'pending',
                            floatval($update['actual_hours'] ?? 0),
                            $update['completion_notes'] ?? '',
                            $taskId,
                            $userId
                        ]);
                    }
                }
                
                // Add unplanned tasks
                if (isset($_POST['unplanned_tasks'])) {
                    foreach ($_POST['unplanned_tasks'] as $task) {
                        if (!empty($task['title'])) {
                            $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, status, actual_hours, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([
                                $task['title'],
                                $task['description'] ?? '',
                                $userId,
                                $today,
                                $task['status'] ?? 'completed',
                                floatval($task['actual_hours'] ?? 0),
                                $task['status'] === 'completed' ? 100 : 50
                            ]);
                        }
                    }
                }
                
                // Update workflow status
                $stmt = $db->prepare("INSERT INTO daily_workflow_status (user_id, workflow_date, evening_updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE evening_updated_at = NOW()");
                $stmt->execute([$userId, $today]);
                
                header('Location: /ergon/daily-workflow/evening-update?success=1');
                exit;
            } catch (Exception $e) {
                error_log('Submit evening updates error: ' . $e->getMessage());
                header('Location: /ergon/daily-workflow/evening-update?error=1');
                exit;
            }
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
                status ENUM('planned','in_progress','completed','cancelled') DEFAULT 'planned',
                progress INT DEFAULT 0,
                completion_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_assigned_date (assigned_to, planned_date),
                INDEX idx_status (status)
            )");
            
            // Create daily_workflow_status table
            $db->exec("CREATE TABLE IF NOT EXISTS daily_workflow_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                workflow_date DATE NOT NULL,
                morning_submitted_at TIMESTAMP NULL,
                evening_updated_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_date (user_id, workflow_date)
            )");
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
                
                // Process submitted plans
                if (isset($_POST['plans'])) {
                    foreach ($_POST['plans'] as $plan) {
                        if (!empty($plan['title'])) {
                            $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
                            $stmt->execute([
                                trim($plan['title']),
                                trim($plan['description'] ?? ''),
                                $userId,
                                $today,
                                $plan['priority'] ?? 'medium',
                                floatval($plan['estimated_hours'] ?? 1)
                            ]);
                        }
                    }
                }
                
                // Update workflow status
                $stmt = $db->prepare("INSERT INTO daily_workflow_status (user_id, workflow_date, morning_submitted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE morning_submitted_at = NOW()");
                $stmt->execute([$userId, $today]);
                
                header('Location: /ergon/daily-workflow/morning-planner?success=1');
                exit;
            } catch (Exception $e) {
                error_log('Submit morning plans error: ' . $e->getMessage());
                header('Location: /ergon/daily-workflow/morning-planner?error=1');
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
                    'estimated_hours' => floatval($_POST['estimated_hours'] ?? 1)
                ];
                
                $sql = "INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $taskData['title'],
                    $taskData['description'],
                    $taskData['assigned_to'],
                    $taskData['planned_date'],
                    $taskData['priority'],
                    $taskData['estimated_hours']
                ]);
                
                echo json_encode(['success' => $result, 'task_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                error_log('Add task error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to add task']);
            }
        }
    }
}
?>