<?php
require_once __DIR__ . '/../models/DailyPlanner.php';
require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class PlannerController {
    private $plannerModel;
    private $departmentModel;
    
    public function __construct() {
        AuthMiddleware::requireAuth();
        $this->plannerModel = new DailyPlanner();
        $this->departmentModel = new Department();
    }
    
    public function calendar() {
        $userId = $_SESSION['user_id'];
        $userDepartment = $_SESSION['user']['department'] ?? null;
        $currentMonth = date('Y-m');
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        
        try {
            $plans = $this->plannerModel->getCalendarData($userId, $startDate, $endDate);
            // Only show user's department
            $departments = $userDepartment ? [['id' => 1, 'name' => $userDepartment]] : [];
        } catch (Exception $e) {
            $plans = [];
            $departments = [];
        }
        
        $data = [
            'plans' => $plans,
            'departments' => $departments,
            'currentMonth' => $currentMonth
        ];
        
        include __DIR__ . '/../views/planner/calendar.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $planData = [
                'user_id' => $_SESSION['user_id'],
                'department_id' => $_POST['department_id'],
                'plan_date' => $_POST['plan_date'],
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? '',
                'priority' => $_POST['priority'],
                'estimated_hours' => $_POST['estimated_hours'] ?? 0,
                'reminder_time' => $_POST['reminder_time'] ?? null
            ];
            
            $result = $this->plannerModel->createPlan($planData);
            if ($result) {
                header('Location: /ergon/planner/calendar?success=created');
                exit;
            }
        }
        
        $userDepartment = $_SESSION['user']['department'] ?? null;
        // Only show user's department
        $departments = $userDepartment ? [['id' => 1, 'name' => $userDepartment]] : [];
        $data = ['departments' => $departments];
        include __DIR__ . '/../views/planner/create.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $planId = $_POST['plan_id'];
            $completionPercentage = $_POST['completion_percentage'];
            $actualHours = $_POST['actual_hours'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            $result = $this->plannerModel->updateProgress($planId, $completionPercentage, $actualHours, $notes);
            
            // Submit department form if provided
            if (isset($_POST['form_data']) && !empty($_POST['form_data'])) {
                $templateId = $_POST['template_id'];
                $this->plannerModel->submitDepartmentForm($templateId, $_SESSION['user_id'], $planId, $_POST['form_data']);
            }
            
            if ($result) {
                header('Location: /ergon/planner/calendar?success=updated');
                exit;
            }
        }
    }
    
    public function getDepartmentForm() {
        $departmentId = $_GET['department_id'] ?? 0;
        $template = $this->plannerModel->getDepartmentFormTemplate($departmentId);
        
        header('Content-Type: application/json');
        echo json_encode($template);
    }
    
    public function getPlansForDate() {
        $userId = $_SESSION['user_id'];
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $plans = $this->plannerModel->getUserPlans($userId, $date);
        
        header('Content-Type: application/json');
        echo json_encode($plans);
    }
}
?>