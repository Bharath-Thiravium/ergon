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
            
            // Query to get actual projects from projects table with task statistics
            $stmt = $db->query("
                SELECT 
                    p.name as project_name,
                    p.status as project_status,
                    p.description,
                    d.name as department_name,
                    COALESCE(COUNT(t.id), 0) as total_tasks,
                    COALESCE(SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_tasks,
                    COALESCE(SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_tasks,
                    COALESCE(SUM(CASE WHEN t.status IN ('assigned', 'pending', 'not_started') THEN 1 ELSE 0 END), 0) as pending_tasks
                FROM projects p
                LEFT JOIN departments d ON p.department_id = d.id
                LEFT JOIN tasks t ON t.project_name = p.name
                WHERE p.status = 'active'
                GROUP BY p.id, p.name, p.status, p.description, d.name
                ORDER BY total_tasks DESC, p.created_at DESC
                LIMIT 10
            ");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get delayed tasks data - check both due_date and deadline columns
            $stmt = $db->query("
                SELECT 
                    t.*,
                    u.name as assigned_user,
                    COALESCE(
                        DATEDIFF(CURDATE(), t.due_date),
                        DATEDIFF(CURDATE(), t.deadline)
                    ) as days_overdue
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE (t.due_date < CURDATE() OR t.deadline < CURDATE())
                AND t.status NOT IN ('completed', 'cancelled')
                ORDER BY COALESCE(t.due_date, t.deadline) ASC
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
}
?>
