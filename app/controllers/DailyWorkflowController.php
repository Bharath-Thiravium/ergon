<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class DailyWorkflowController extends Controller {
    
    // Daily Planner Interface (Continuous Updates)
    public function morningPlanner() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $title = 'Daily Planner';
        $active_page = 'planner';
        
        try {
            $db = Database::connect();
            $today = date('Y-m-d');
            
            // Get user's departments (multiple departments support)
            $stmt = $db->prepare("SELECT department FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userDeptString = $stmt->fetchColumn();
            
            // Parse multiple departments (comma-separated)
            $userDepts = array_map('trim', explode(',', $userDeptString));
            $deptIds = [];
            $deptNames = [];
            
            foreach ($userDepts as $deptName) {
                if (!empty($deptName)) {
                    $stmt = $db->prepare("SELECT id, name FROM departments WHERE name = ?");
                    $stmt->execute([$deptName]);
                    $dept = $stmt->fetch();
                    if ($dept) {
                        $deptIds[] = $dept['id'];
                        $deptNames[] = $dept['name'];
                    }
                }
            }
            
            // Get task categories for ALL user's departments
            $taskCategories = [];
            if (!empty($deptNames)) {
                $placeholders = str_repeat('?,', count($deptNames) - 1) . '?';
                $stmt = $db->prepare("SELECT * FROM task_categories WHERE department_name IN ($placeholders) AND is_active = 1 ORDER BY category_name");
                $stmt->execute($deptNames);
                $taskCategories = $stmt->fetchAll();
            }
            
            // Get projects for ALL user's departments and general projects
            $projects = [];
            if (!empty($deptIds)) {
                $placeholders = str_repeat('?,', count($deptIds) - 1) . '?';
                $stmt = $db->prepare("SELECT * FROM projects WHERE status = 'active' AND (department_id IN ($placeholders) OR department_id IS NULL) ORDER BY name");
                $stmt->execute($deptIds);
                $projects = $stmt->fetchAll();
            } else {
                $stmt = $db->prepare("SELECT * FROM projects WHERE status = 'active' AND department_id IS NULL ORDER BY name");
                $stmt->execute();
                $projects = $stmt->fetchAll();
            }
            
            // Get today's plans with follow-up info
            $stmt = $db->prepare("SELECT dp.*, p.name as project_display_name FROM daily_plans dp LEFT JOIN projects p ON dp.project_name = p.name WHERE dp.user_id = ? AND dp.plan_date = ? ORDER BY dp.status ASC, dp.priority DESC, dp.created_at ASC");
            $stmt->execute([$_SESSION['user_id'], $today]);
            $todayPlans = $stmt->fetchAll();
            
            // Get user's department objects for the form
            $userDepartments = [];
            foreach ($userDepts as $deptName) {
                if (!empty($deptName)) {
                    $stmt = $db->prepare("SELECT id, name FROM departments WHERE name = ?");
                    $stmt->execute([$deptName]);
                    $dept = $stmt->fetch();
                    if ($dept) {
                        $userDepartments[] = $dept;
                    }
                }
            }
            
            $data = [
                'userDepts' => $deptNames,
                'userDepartments' => $userDepartments,
                'taskCategories' => $taskCategories,
                'projects' => $projects,
                'todayPlans' => $todayPlans
            ];
            
            ob_start();
            include __DIR__ . '/../../views/daily_workflow/daily_planner.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            error_log('Daily Planner Error: ' . $e->getMessage());
            header('Location: /ergon/dashboard?error=planner_failed');
            exit;
        }
    }
    
    // Evening Update - redirect to main planner (continuous updates)
    public function eveningUpdate() {
        header('Location: /ergon/daily-workflow/morning-planner');
        exit;
    }
    
    // Submit Morning Plans
    public function submitMorningPlans() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/daily-workflow/morning-planner');
            exit;
        }
        
        try {
            $db = Database::connect();
            $today = date('Y-m-d');
            
            $db->beginTransaction();
            
            // Create or update workflow status
            $stmt = $db->prepare("INSERT INTO daily_workflow_status (user_id, workflow_date, morning_submitted, morning_submitted_at, total_planned_tasks, total_planned_hours) 
                                 VALUES (?, ?, TRUE, NOW(), ?, ?) 
                                 ON DUPLICATE KEY UPDATE morning_submitted = TRUE, morning_submitted_at = NOW(), total_planned_tasks = ?, total_planned_hours = ?");
            
            $totalTasks = count($_POST['plans'] ?? []);
            $totalHours = array_sum(array_column($_POST['plans'] ?? [], 'estimated_hours'));
            
            $stmt->execute([$_SESSION['user_id'], $today, $totalTasks, $totalHours, $totalTasks, $totalHours]);
            
            // Insert/Update plans
            foreach ($_POST['plans'] as $plan) {
                if (!empty($plan['title'])) {
                    $stmt = $db->prepare("INSERT INTO daily_plans (user_id, plan_date, title, description, priority, estimated_hours, category, submitted_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $today,
                        $plan['title'],
                        $plan['description'] ?? '',
                        $plan['priority'] ?? 'medium',
                        $plan['estimated_hours'] ?? 1.0,
                        $plan['category'] ?? 'planned'
                    ]);
                }
            }
            
            $db->commit();
            header('Location: /ergon/daily-workflow/evening-update?success=morning_submitted');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Submit Morning Plans Error: ' . $e->getMessage());
            header('Location: /ergon/daily-workflow/morning-planner?error=submit_failed');
            exit;
        }
    }
    
    // Submit Evening Updates
    public function submitEveningUpdates() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/daily-workflow/evening-update');
            exit;
        }
        
        try {
            $db = Database::connect();
            $today = date('Y-m-d');
            
            $db->beginTransaction();
            
            $completedTasks = 0;
            $totalActualHours = 0;
            
            // Update each plan
            foreach ($_POST['updates'] as $planId => $update) {
                $stmt = $db->prepare("UPDATE daily_plans SET progress = ?, status = ?, actual_hours = ?, completion_notes = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $update['progress'],
                    $update['status'],
                    $update['actual_hours'] ?? 0,
                    $update['completion_notes'] ?? '',
                    $planId,
                    $_SESSION['user_id']
                ]);
                
                // Award points for task completion
                if ($update['status'] === 'completed') {
                    require_once __DIR__ . '/../models/Gamification.php';
                    $gamification = new Gamification();
                    $points = match($update['priority'] ?? 'medium') {
                        'urgent' => 15,
                        'high' => 10,
                        'medium' => 5,
                        'low' => 3,
                        default => 5
                    };
                    $gamification->addPoints($_SESSION['user_id'], $points, 'Task completed', 'task', $planId);
                    $completedTasks++;
                }
                
                // Log the update
                $stmt = $db->prepare("INSERT INTO daily_task_updates (plan_id, progress_after, hours_worked, update_notes, update_type) VALUES (?, ?, ?, ?, 'progress')");
                $stmt->execute([
                    $planId,
                    $update['progress'],
                    $update['actual_hours'] ?? 0,
                    $update['completion_notes'] ?? ''
                ]);
                
                $totalActualHours += floatval($update['actual_hours'] ?? 0);
            }
            
            // Add unplanned tasks
            if (!empty($_POST['unplanned_tasks'])) {
                foreach ($_POST['unplanned_tasks'] as $task) {
                    if (!empty($task['title'])) {
                        $stmt = $db->prepare("INSERT INTO daily_plans (user_id, plan_date, title, description, category, actual_hours, status, progress, completion_notes) 
                                             VALUES (?, ?, ?, ?, 'unplanned', ?, ?, ?, ?)");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            $today,
                            $task['title'],
                            $task['description'] ?? '',
                            $task['actual_hours'] ?? 0,
                            $task['status'] ?? 'completed',
                            $task['progress'] ?? 100,
                            $task['completion_notes'] ?? ''
                        ]);
                        
                        if (($task['status'] ?? 'completed') === 'completed') $completedTasks++;
                        $totalActualHours += floatval($task['actual_hours'] ?? 0);
                    }
                }
            }
            
            // Calculate productivity score
            $stmt = $db->prepare("SELECT total_planned_hours FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$_SESSION['user_id'], $today]);
            $plannedHours = $stmt->fetchColumn() ?: 1;
            
            $productivityScore = min(100, ($totalActualHours / $plannedHours) * 100);
            
            // Update workflow status
            $stmt = $db->prepare("UPDATE daily_workflow_status SET evening_updated = TRUE, evening_updated_at = NOW(), total_completed_tasks = ?, total_actual_hours = ?, productivity_score = ? WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$completedTasks, $totalActualHours, $productivityScore, $_SESSION['user_id'], $today]);
            
            $db->commit();
            header('Location: /ergon/dashboard?success=day_completed');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Submit Evening Updates Error: ' . $e->getMessage());
            header('Location: /ergon/daily-workflow/evening-update?error=submit_failed');
            exit;
        }
    }
    
    // Progress Dashboard (for Admins/Owners)
    public function progressDashboard() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $title = 'Progress Dashboard';
        $active_page = 'daily-planner-dashboard';
        
        try {
            $db = Database::connect();
            $today = date('Y-m-d');
            
            // Get team progress
            $stmt = $db->prepare("SELECT u.name, u.id, dws.*, 
                                 (SELECT COUNT(*) FROM daily_plans dp WHERE dp.user_id = u.id AND dp.plan_date = ?) as total_tasks,
                                 (SELECT COUNT(*) FROM daily_plans dp WHERE dp.user_id = u.id AND dp.plan_date = ? AND dp.status = 'completed') as completed_tasks
                                 FROM users u 
                                 LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date = ?
                                 WHERE u.role = 'user' AND u.status = 'active'
                                 ORDER BY dws.productivity_score DESC");
            $stmt->execute([$today, $today, $today]);
            $teamProgress = $stmt->fetchAll();
            
            // Get delayed/blocked tasks
            $stmt = $db->prepare("SELECT dp.*, u.name as user_name FROM daily_plans dp 
                                 JOIN users u ON dp.user_id = u.id 
                                 WHERE dp.status IN ('blocked', 'pending') AND dp.plan_date <= ? 
                                 ORDER BY dp.plan_date ASC, dp.priority DESC");
            $stmt->execute([$today]);
            $delayedTasks = $stmt->fetchAll();
            
            $data = [
                'teamProgress' => $teamProgress,
                'delayedTasks' => $delayedTasks,
                'selectedDate' => $today
            ];
            
            ob_start();
            include __DIR__ . '/../../views/daily_workflow/progress_dashboard.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            error_log('Progress Dashboard Error: ' . $e->getMessage());
            header('Location: /ergon/dashboard?error=progress_failed');
            exit;
        }
    }
    
    // Get task categories for department
    public function getTaskCategories() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $department = $_GET['department'] ?? '';
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
            $stmt->execute([$department]);
            $categories = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Delete Task
    public function deleteTask() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            $taskId = $_POST['task_id'];
            
            // Admin/Owner can delete any task, users can only delete their own
            if (in_array($_SESSION['role'], ['admin', 'owner'])) {
                $stmt = $db->prepare("SELECT user_id, plan_date FROM daily_plans WHERE id = ?");
                $stmt->execute([$taskId]);
                $task = $stmt->fetch();
                
                if (!$task) {
                    echo json_encode(['success' => false, 'error' => 'Task not found']);
                    return;
                }
                
                $stmt = $db->prepare("DELETE FROM daily_plans WHERE id = ?");
                $stmt->execute([$taskId]);
            } else {
                $stmt = $db->prepare("SELECT user_id, plan_date FROM daily_plans WHERE id = ? AND user_id = ?");
                $stmt->execute([$taskId, $_SESSION['user_id']]);
                $task = $stmt->fetch();
                
                if (!$task) {
                    echo json_encode(['success' => false, 'error' => 'Task not found']);
                    return;
                }
                
                $stmt = $db->prepare("DELETE FROM daily_plans WHERE id = ? AND user_id = ?");
                $stmt->execute([$taskId, $_SESSION['user_id']]);
            }
            
            // Update workflow stats
            $this->updateWorkflowStats($task['user_id'], $task['plan_date']);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Delete User Workflow (Admin/Owner only)
    public function deleteUserWorkflow() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $userId = $_POST['user_id'];
            $date = $_POST['date'];
            
            $db->beginTransaction();
            
            // Delete all tasks for the user on the specified date
            $stmt = $db->prepare("DELETE FROM daily_plans WHERE user_id = ? AND plan_date = ?");
            $stmt->execute([$userId, $date]);
            
            // Delete workflow status
            $stmt = $db->prepare("DELETE FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([$userId, $date]);
            
            $db->commit();
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Add Task (AJAX)
    public function addTask() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            // Get user's department
            $stmt = $db->prepare("SELECT department FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userDept = $stmt->fetchColumn();
            
            $deptId = null;
            if ($userDept) {
                $stmt = $db->prepare("SELECT id FROM departments WHERE name = ?");
                $stmt->execute([$userDept]);
                $deptId = $stmt->fetchColumn() ?: null;
            }
            
            $stmt = $db->prepare("INSERT INTO daily_plans (user_id, department_id, plan_date, project_name, title, task_category, description, priority, estimated_hours, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $deptId,
                $_POST['plan_date'] ?? date('Y-m-d'),
                $_POST['project_name'] ?? '',
                $_POST['title'],
                $_POST['task_category'] ?? '',
                $_POST['description'] ?? '',
                $_POST['priority'] ?? 'medium',
                $_POST['estimated_hours'] ?? 1.0,
                $_POST['category'] ?? 'planned'
            ]);
            
            $this->updateWorkflowStats($_SESSION['user_id'], $_POST['plan_date'] ?? date('Y-m-d'));
            
            echo json_encode(['success' => $result, 'task_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Update Task (AJAX)
    public function updateTask() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $taskId = $_POST['task_id'];
            $progress = $_POST['progress'] ?? 0;
            $status = $_POST['status'] ?? 'pending';
            $actualHours = $_POST['actual_hours'] ?? 0;
            $notes = $_POST['completion_notes'] ?? '';
            
            // Get current progress for logging
            $stmt = $db->prepare("SELECT progress, plan_date FROM daily_plans WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $_SESSION['user_id']]);
            $current = $stmt->fetch();
            
            if (!$current) {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
                return;
            }
            
            // Update task
            $completedAt = ($status === 'completed') ? 'NOW()' : 'NULL';
            $stmt = $db->prepare("UPDATE daily_plans SET progress = ?, status = ?, actual_hours = ?, completion_notes = ?, completed_at = $completedAt, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$progress, $status, $actualHours, $notes, $taskId, $_SESSION['user_id']]);
            
            // Log the update
            $stmt = $db->prepare("INSERT INTO daily_task_updates (plan_id, progress_before, progress_after, hours_worked, update_notes, update_type) VALUES (?, ?, ?, ?, ?, 'progress')");
            $stmt->execute([$taskId, $current['progress'], $progress, $actualHours, $notes]);
            
            // Update workflow stats
            $this->updateWorkflowStats($_SESSION['user_id'], $current['plan_date']);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    private function updateWorkflowStats($userId, $date) {
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(estimated_hours) as planned_hours, 
                                 SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                                 SUM(actual_hours) as actual_hours
                                 FROM daily_plans WHERE user_id = ? AND plan_date = ?");
            $stmt->execute([$userId, $date]);
            $stats = $stmt->fetch();
            
            $productivityScore = $stats['planned_hours'] > 0 ? 
                min(100, ($stats['actual_hours'] / $stats['planned_hours']) * 100) : 0;
            
            $stmt = $db->prepare("UPDATE daily_workflow_status SET 
                                 total_planned_tasks = ?, total_completed_tasks = ?, 
                                 total_planned_hours = ?, total_actual_hours = ?, 
                                 productivity_score = ? 
                                 WHERE user_id = ? AND workflow_date = ?");
            $stmt->execute([
                $stats['total'], $stats['completed'], 
                $stats['planned_hours'], $stats['actual_hours'], 
                $productivityScore, $userId, $date
            ]);
        } catch (Exception $e) {
            error_log('Update workflow stats error: ' . $e->getMessage());
        }
    }
    
    private function getAllDepartments() {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getProjectsByDepartment() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $departmentId = $_GET['department_id'] ?? null;
        
        try {
            $db = Database::connect();
            
            if ($departmentId) {
                $stmt = $db->prepare("SELECT * FROM projects WHERE status = 'active' AND (department_id = ? OR department_id IS NULL) ORDER BY name");
                $stmt->execute([$departmentId]);
            } else {
                $stmt = $db->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
                $stmt->execute();
            }
            
            $projects = $stmt->fetchAll();
            echo json_encode(['success' => true, 'projects' => $projects]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function getTaskCategoriesByDepartment() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Allow access if user is logged in or for testing purposes
        if (!isset($_SESSION['user_id']) && !isset($_GET['test'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $departmentName = $_GET['department_name'] ?? '';
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
            $stmt->execute([$departmentName]);
            $categories = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'categories' => $categories]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}