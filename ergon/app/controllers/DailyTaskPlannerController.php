<?php

class DailyTaskPlannerController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $title = 'Daily Progress Report';
        $active_page = 'daily-planner';
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM daily_task_planner WHERE user_id = ? AND DATE(created_at) = CURDATE()");
            $stmt->execute([$_SESSION['user_id']]);
            $todayTasks = $stmt->fetchAll();
            
            $data = ['todayTasks' => $todayTasks];
            
            ob_start();
            include __DIR__ . '/../../views/daily_planner/index.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to load daily planner');
        }
    }
    
    public function dashboard() {
        $this->requireAuth(['admin', 'owner']);
        
        $title = 'Progress Dashboard';
        $active_page = 'daily-planner-dashboard';
        
        $projectProgress = [
            ['project_name' => 'ERGON Development', 'completed_tasks' => 15, 'completion_percentage' => 75],
            ['project_name' => 'Client Portal', 'completed_tasks' => 8, 'completion_percentage' => 60],
            ['project_name' => 'Mobile App', 'completed_tasks' => 12, 'completion_percentage' => 85]
        ];
        
        $delayedTasks = [
            ['task_name' => 'Database Optimization', 'completion_percentage' => 30, 'days_since_update' => 3],
            ['task_name' => 'UI Testing', 'completion_percentage' => 45, 'days_since_update' => 2]
        ];
        
        $teamActivity = [
            ['name' => 'John Doe', 'department' => 'IT', 'tasks_updated' => 3, 'avg_progress' => 75, 'total_hours' => 8],
            ['name' => 'Jane Smith', 'department' => 'Marketing', 'tasks_updated' => 2, 'avg_progress' => 60, 'total_hours' => 6],
            ['name' => 'Mike Johnson', 'department' => 'Sales', 'tasks_updated' => 4, 'avg_progress' => 90, 'total_hours' => 7]
        ];
        
        $selectedDepartment = $_GET['department'] ?? '';
        $today = date('Y-m-d');
        
        include __DIR__ . '/../../views/daily_planner/dashboard.php';
    }
    
    public function submitTask() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO daily_task_planner (user_id, project_name, task_description, progress, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['project_name'],
                    $_POST['task_description'],
                    $_POST['progress'],
                    $_POST['status']
                ]);
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
    
    public function getProjectTasks() {
        $this->requireAuth();
        
        try {
            $project = $_GET['project'] ?? '';
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM tasks WHERE project_name = ? AND status != 'completed'");
            $stmt->execute([$project]);
            $tasks = $stmt->fetchAll();
            
            echo json_encode(['tasks' => $tasks]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function projectOverview() {
        $data = [
            'projectProgress' => [
                ['id' => 1, 'name' => 'ERGON Development', 'department' => 'IT', 'total_tasks' => 20, 'completed_tasks' => 15, 'completion_percentage' => 75],
                ['id' => 2, 'name' => 'Client Portal', 'department' => 'Sales', 'total_tasks' => 12, 'completed_tasks' => 8, 'completion_percentage' => 67],
                ['id' => 3, 'name' => 'Mobile App', 'department' => 'IT', 'total_tasks' => 18, 'completed_tasks' => 14, 'completion_percentage' => 78],
                ['id' => 4, 'name' => 'Marketing Campaign', 'department' => 'Marketing', 'total_tasks' => 10, 'completed_tasks' => 6, 'completion_percentage' => 60]
            ]
        ];
        
        include __DIR__ . '/../../views/daily_planner/project_overview.php';
    }
    
    public function delayedTasksOverview() {
        $data = [
            'delayedTasks' => [
                ['id' => 1, 'task_name' => 'Database Optimization', 'user_name' => 'John Doe', 'completion_percentage' => 30, 'days_overdue' => 3, 'priority' => 'high'],
                ['id' => 2, 'task_name' => 'UI Testing', 'user_name' => 'Jane Smith', 'completion_percentage' => 45, 'days_overdue' => 2, 'priority' => 'medium'],
                ['id' => 3, 'task_name' => 'API Integration', 'user_name' => 'Mike Johnson', 'completion_percentage' => 25, 'days_overdue' => 5, 'priority' => 'high'],
                ['id' => 4, 'task_name' => 'Code Review', 'user_name' => 'Sarah Wilson', 'completion_percentage' => 60, 'days_overdue' => 1, 'priority' => 'low']
            ]
        ];
        
        include __DIR__ . '/../../views/daily_planner/delayed_tasks_overview.php';
    }
    
    public function projectProgressApi() {
        $this->requireAuth(['admin', 'owner']);
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT project_name, AVG(progress) as progress, COUNT(*) as task_count
                                 FROM daily_task_planner 
                                 WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
                                 GROUP BY project_name");
            $stmt->execute();
            $progress = $stmt->fetchAll();
            
            echo json_encode(['progress' => $progress]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}