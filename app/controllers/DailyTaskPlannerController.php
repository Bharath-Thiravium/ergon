<?php
require_once __DIR__ . '/../models/DailyTaskPlanner.php';
require_once __DIR__ . '/../core/Controller.php';

class DailyTaskPlannerController extends Controller {
    private $dailyTaskPlanner;
    
    public function __construct() {
        parent::__construct();
        $this->dailyTaskPlanner = new DailyTaskPlanner();
    }
    
    // Daily planner form
    public function index() {
        $this->requireAuth();
        
        $user = $_SESSION['user'];
        $today = date('Y-m-d');
        
        // Get user's today tasks
        $todayTasks = $this->dailyTaskPlanner->getUserDailyTasks($user['id'], $today);
        
        // Get available projects for user's department
        $projects = $this->dailyTaskPlanner->getProjectsByDepartment($user['department']);
        
        // Get task categories for user's department
        $taskCategories = $this->dailyTaskPlanner->getTaskCategories($user['department']);
        
        $this->render('daily_planner/index', [
            'todayTasks' => $todayTasks,
            'projects' => $projects,
            'taskCategories' => $taskCategories,
            'today' => $today
        ]);
    }
    
    // Get tasks for selected project (AJAX)
    public function getProjectTasks() {
        $this->requireAuth();
        
        $projectId = $_GET['project_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'Project ID required']);
            return;
        }
        
        $tasks = $this->dailyTaskPlanner->getProjectTasks($projectId, $categoryId);
        echo json_encode($tasks);
    }
    
    // Submit daily task
    public function submitTask() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/daily-planner');
            return;
        }
        
        $user = $_SESSION['user'];
        $attachmentPath = null;
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../storage/task_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath)) {
                $attachmentPath = 'storage/task_attachments/' . $fileName;
            }
        }
        
        $data = [
            'user_id' => $user['id'],
            'project_id' => $_POST['project_id'],
            'task_id' => $_POST['task_id'],
            'entry_date' => $_POST['entry_date'] ?? date('Y-m-d'),
            'progress_percentage' => $_POST['progress_percentage'],
            'hours_spent' => $_POST['hours_spent'] ?? 0,
            'work_notes' => $_POST['work_notes'] ?? '',
            'attachment_path' => $attachmentPath,
            'gps_latitude' => $_POST['gps_latitude'] ?? null,
            'gps_longitude' => $_POST['gps_longitude'] ?? null
        ];
        
        if ($this->dailyTaskPlanner->submitDailyTask($data)) {
            $_SESSION['success'] = 'Task updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update task. Please try again.';
        }
        
        $this->redirect('/daily-planner');
    }
    
    // Manager dashboard
    public function dashboard() {
        $this->requireAuth(['admin', 'owner']);
        
        $today = date('Y-m-d');
        $department = $_GET['department'] ?? null;
        
        // Get project progress
        $projectProgress = $this->dailyTaskPlanner->getProjectProgress();
        
        // Get team daily activity
        $teamActivity = $this->dailyTaskPlanner->getTeamDailyActivity($today, $department);
        
        // Get delayed tasks
        $delayedTasks = $this->dailyTaskPlanner->getDelayedTasks();
        
        $this->render('daily_planner/dashboard', [
            'projectProgress' => $projectProgress,
            'teamActivity' => $teamActivity,
            'delayedTasks' => $delayedTasks,
            'selectedDepartment' => $department,
            'today' => $today
        ]);
    }
    
    // Project progress API
    public function projectProgressApi() {
        $this->requireAuth();
        
        $projectProgress = $this->dailyTaskPlanner->getProjectProgress();
        
        // Format for charts
        $chartData = [
            'labels' => array_column($projectProgress, 'name'),
            'data' => array_column($projectProgress, 'completion_percentage')
        ];
        
        echo json_encode($chartData);
    }
}
?>