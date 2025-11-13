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
            
            // Get project progress data
            $stmt = $db->query("
                SELECT 
                    t.project_name,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks
                FROM tasks t 
                WHERE t.project_name IS NOT NULL AND t.project_name != ''
                GROUP BY t.project_name
                ORDER BY total_tasks DESC
            ");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'projects' => $projects,
                'active_page' => 'dashboard'
            ];
            
            $this->view('dashboard/project_overview', $data);
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
            
            // Get delayed tasks data
            $stmt = $db->query("
                SELECT 
                    t.*,
                    u.name as assigned_user,
                    DATEDIFF(CURDATE(), t.due_date) as days_overdue
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.due_date < CURDATE() 
                AND t.status != 'completed'
                ORDER BY t.due_date ASC
            ");
            $delayedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'delayed_tasks' => $delayedTasks,
                'active_page' => 'dashboard'
            ];
            
            $this->view('dashboard/delayed_tasks_overview', $data);
        } catch (Exception $e) {
            error_log('Delayed tasks overview error: ' . $e->getMessage());
            $this->view('dashboard/delayed_tasks_overview', ['delayed_tasks' => [], 'active_page' => 'dashboard']);
        }
    }
}
?>
