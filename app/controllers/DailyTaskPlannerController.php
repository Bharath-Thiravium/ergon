<?php
require_once __DIR__ . '/../models/DailyTaskPlanner.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DailyTaskPlannerController extends Controller {
    private $dailyTaskPlanner;
    
    public function __construct() {
        $this->dailyTaskPlanner = new DailyTaskPlanner();
    }
    
    private function requireAuth($roles = null) {
        AuthMiddleware::requireAuth($roles);
    }
    
    private function render($view, $data = []) {
        $this->view($view, $data);
    }
    
    // Daily planner form
    public function index() {
        $this->requireAuth(); // No role restriction - all users can access
        
        // Get user data from database if not in session
        if (!isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $userData = $userModel->getById($_SESSION['user_id']);
            $_SESSION['user'] = $userData;
        }
        
        $user = $_SESSION['user'] ?? [];
        $userDepartment = $user['department'] ?? 'General';
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        // Get user's today tasks
        $todayTasks = $this->dailyTaskPlanner->getUserDailyTasks($userId, $today);
        
        // Get available projects for user's department ONLY
        $projects = $this->dailyTaskPlanner->getProjectsByDepartment($userDepartment);
        
        // Get task categories for user's department ONLY
        $taskCategories = $this->dailyTaskPlanner->getTaskCategories($userDepartment);
        
        $this->render('daily_planner/index', [
            'todayTasks' => $todayTasks,
            'projects' => $projects,
            'taskCategories' => $taskCategories,
            'today' => $today,
            'userDepartment' => $userDepartment
        ]);
    }
    
    // Get tasks for selected project (AJAX)
    public function getProjectTasks() {
        $this->requireAuth(); // No role restriction - all users can access
        
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
        $this->requireAuth(); // No role restriction - all users can submit
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/daily-planner');
            return;
        }
        
        // Get user data from database if not in session
        if (!isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $userData = $userModel->getById($_SESSION['user_id']);
            $_SESSION['user'] = $userData;
        }
        
        $userId = $_SESSION['user_id'];
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
            'user_id' => $userId,
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
        
        try {
            // Get project progress (filtered by department if specified)
            $projectProgress = $this->dailyTaskPlanner->getProjectProgress($department);
            
            // Get team daily activity
            $teamActivity = $this->dailyTaskPlanner->getTeamDailyActivity($today, $department);
            
            // Get delayed tasks
            $delayedTasks = $this->dailyTaskPlanner->getDelayedTasks($department);
            
            // Get available departments for filter
            require_once __DIR__ . '/../models/Department.php';
            $departmentModel = new Department();
            $departments = $departmentModel->getAll();
            
            $this->render('daily_planner/dashboard', [
                'projectProgress' => $projectProgress,
                'teamActivity' => $teamActivity,
                'delayedTasks' => $delayedTasks,
                'departments' => $departments,
                'selectedDepartment' => $department,
                'today' => $today
            ]);
        } catch (Exception $e) {
            error_log('Dashboard error: ' . $e->getMessage());
            $this->render('daily_planner/dashboard', [
                'projectProgress' => [],
                'teamActivity' => [],
                'delayedTasks' => [],
                'departments' => [],
                'selectedDepartment' => $department,
                'today' => $today,
                'error' => 'Unable to load dashboard data. Please try again.'
            ]);
        }
    }
    
    // Project overview popup
    public function projectOverview() {
        $this->requireAuth(['admin', 'owner']);
        
        $projectId = $_GET['project_id'] ?? null;
        $department = $_GET['department'] ?? null;
        
        if ($projectId) {
            // Get specific project details
            $projectDetails = $this->dailyTaskPlanner->getProjectDetails($projectId);
            $projectTasks = $this->dailyTaskPlanner->getProjectTasks($projectId);
            $projectProgress = $this->dailyTaskPlanner->getProjectProgressById($projectId);
            
            $this->render('daily_planner/project_overview', [
                'projectDetails' => $projectDetails,
                'projectTasks' => $projectTasks,
                'projectProgress' => $projectProgress,
                'selectedDepartment' => $department
            ]);
        } else {
            // Get all projects overview
            $projectProgress = $this->dailyTaskPlanner->getProjectProgress($department);
            
            $this->render('daily_planner/project_overview', [
                'data' => ['projectProgress' => $projectProgress],
                'selectedDepartment' => $department
            ]);
        }
    }
    
    // Delayed tasks overview
    public function delayedTasksOverview() {
        $this->requireAuth(['admin', 'owner']);
        
        $delayedTasks = $this->dailyTaskPlanner->getDelayedTasks();
        
        $this->render('daily_planner/delayed_tasks_overview', [
            'data' => ['delayedTasks' => $delayedTasks]
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