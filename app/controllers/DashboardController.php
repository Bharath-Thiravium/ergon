<?php
/**
 * Dashboard Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DashboardController extends Controller {
    
    public function index() {
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
            
            // Simple query to get project data from tasks
            $stmt = $db->query("
                SELECT 
                    COALESCE(project_name, 'General Tasks') as project_name,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status IN ('assigned', 'pending') THEN 1 ELSE 0 END) as pending_tasks
                FROM tasks 
                GROUP BY COALESCE(project_name, 'General Tasks')
                ORDER BY total_tasks DESC
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
