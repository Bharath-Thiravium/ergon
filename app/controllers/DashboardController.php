<?php
/**
 * Dashboard Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DashboardController extends Controller {
    
    public function index() {
        // Debug session info
        error_log('Dashboard access - Session ID: ' . session_id());
        error_log('Dashboard access - User ID: ' . ($_SESSION['user_id'] ?? 'none'));
        error_log('Dashboard access - Role: ' . ($_SESSION['role'] ?? 'none'));
        
        AuthMiddleware::requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        
        switch ($role) {
            case 'owner':
                $this->redirect('/owner/dashboard');
                break;
            case 'admin':
                $this->redirect('/admin/dashboard');
                break;
            default:
                $this->redirect('/user/dashboard');
                break;
        }
    }
    
    public function projectOverview() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Direct query from tasks table
            $stmt = $db->query("
                SELECT 
                    COALESCE(NULLIF(project_name, ''), 'General Tasks') as project_name,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status NOT IN ('completed', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks
                FROM tasks
                GROUP BY COALESCE(NULLIF(project_name, ''), 'General Tasks')
                HAVING COUNT(*) > 0
                ORDER BY total_tasks DESC
                LIMIT 10
            ");
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $this->view('dashboard/project_overview', [
                'projects' => $projects,
                'active_page' => 'dashboard'
            ]);
        } catch (Exception $e) {
            $this->view('dashboard/project_overview', ['projects' => [], 'active_page' => 'dashboard']);
        }
    }
    
    public function delayedTasksOverview() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Simple query for overdue tasks
            $stmt = $db->query("
                SELECT 
                    t.*,
                    u.name as assigned_user,
                    1 as days_overdue
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.status NOT IN ('completed', 'cancelled')
                AND (
                    (t.due_date IS NOT NULL AND t.due_date < NOW()) OR
                    (t.deadline IS NOT NULL AND t.deadline < NOW())
                )
                ORDER BY t.created_at DESC
                LIMIT 50
            ");
            
            $delayedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $this->view('dashboard/delayed_tasks_overview', [
                'delayed_tasks' => $delayedTasks,
                'active_page' => 'dashboard'
            ]);
        } catch (Exception $e) {
            $this->view('dashboard/delayed_tasks_overview', ['delayed_tasks' => [], 'active_page' => 'dashboard']);
        }
    }
    
    public function projectTasksOverview() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get projects with their tasks
            $stmt = $db->query("
                SELECT 
                    p.name as project_name,
                    p.description as project_description,
                    d.name as department_name,
                    t.id as task_id,
                    t.title as task_title,
                    t.description as task_description,
                    t.status as task_status,
                    t.priority,
                    t.due_date,
                    t.deadline,
                    u.name as assigned_user
                FROM projects p
                LEFT JOIN departments d ON p.department_id = d.id
                LEFT JOIN tasks t ON t.project_name = p.name
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE p.status = 'active'
                ORDER BY p.name, t.priority DESC, t.created_at DESC
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group tasks by project
            $projects = [];
            foreach ($results as $row) {
                $projectName = $row['project_name'];
                if (!isset($projects[$projectName])) {
                    $projects[$projectName] = [
                        'name' => $projectName,
                        'description' => $row['project_description'],
                        'department' => $row['department_name'],
                        'tasks' => []
                    ];
                }
                if ($row['task_id']) {
                    $projects[$projectName]['tasks'][] = [
                        'id' => $row['task_id'],
                        'title' => $row['task_title'],
                        'description' => $row['task_description'],
                        'status' => $row['task_status'],
                        'priority' => $row['priority'],
                        'due_date' => $row['due_date'],
                        'deadline' => $row['deadline'],
                        'assigned_user' => $row['assigned_user']
                    ];
                }
            }
            
            $this->view('dashboard/project_tasks_overview', [
                'projects' => array_values($projects),
                'active_page' => 'dashboard'
            ]);
        } catch (Exception $e) {
            error_log('Project tasks overview error: ' . $e->getMessage());
            $this->view('dashboard/project_tasks_overview', ['projects' => [], 'active_page' => 'dashboard']);
        }
    }
}
?>
