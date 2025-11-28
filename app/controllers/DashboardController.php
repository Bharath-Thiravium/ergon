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
        
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Simplified query - get projects from tasks if projects table is empty
            $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
            $projectTableCount = $stmt->fetchColumn();
            
            if ($projectTableCount > 0) {
                // Use projects table
                $stmt = $db->query("
                    SELECT 
                        p.name as project_name,
                        COUNT(t.id) as total_tasks,
                        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN t.status IN ('assigned', 'pending', 'not_started') THEN 1 ELSE 0 END) as pending_tasks
                    FROM projects p
                    LEFT JOIN tasks t ON (t.project_name = p.name OR t.project_id = p.id)
                    WHERE p.status = 'active'
                    GROUP BY p.name
                    ORDER BY total_tasks DESC
                    LIMIT 10
                ");
            } else {
                // Fallback to tasks table
                $stmt = $db->query("
                    SELECT 
                        COALESCE(project_name, 'General Tasks') as project_name,
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN status IN ('assigned', 'pending', 'not_started') THEN 1 ELSE 0 END) as pending_tasks
                    FROM tasks
                    GROUP BY COALESCE(project_name, 'General Tasks')
                    ORDER BY total_tasks DESC
                    LIMIT 10
                ");
            }
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($projects as &$project) {
                $project['total_tasks'] = (int)($project['total_tasks'] ?? 0);
                $project['completed_tasks'] = (int)($project['completed_tasks'] ?? 0);
                $project['in_progress_tasks'] = (int)($project['in_progress_tasks'] ?? 0);
                $project['pending_tasks'] = (int)($project['pending_tasks'] ?? 0);
            }
            
            $this->view('dashboard/project_overview', [
                'projects' => $projects,
                'active_page' => 'dashboard'
            ]);
        } catch (Exception $e) {
            error_log('Project overview error: ' . $e->getMessage());
            $this->view('dashboard/project_overview', ['projects' => [], 'active_page' => 'dashboard']);
        }
    }
    
    public function delayedTasksOverview() {
        AuthMiddleware::requireAuth();
        
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Simplified query for delayed tasks
            $stmt = $db->query("
                SELECT 
                    t.*,
                    u.name as assigned_user,
                    CASE 
                        WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN DATEDIFF(CURDATE(), t.due_date)
                        WHEN t.deadline IS NOT NULL AND t.deadline < CURDATE() THEN DATEDIFF(CURDATE(), t.deadline)
                        ELSE 0
                    END as days_overdue
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE (
                    (t.due_date IS NOT NULL AND t.due_date < CURDATE()) OR 
                    (t.deadline IS NOT NULL AND t.deadline < CURDATE())
                )
                AND t.status NOT IN ('completed', 'cancelled')
                ORDER BY 
                    CASE 
                        WHEN t.due_date IS NOT NULL THEN t.due_date
                        ELSE t.deadline
                    END ASC
                LIMIT 50
            ");
            $delayedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('dashboard/delayed_tasks_overview', [
                'delayed_tasks' => $delayedTasks,
                'active_page' => 'dashboard'
            ]);
        } catch (Exception $e) {
            error_log('Delayed tasks overview error: ' . $e->getMessage());
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
