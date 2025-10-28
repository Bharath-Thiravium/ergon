<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class DailyWorkflowController extends Controller {
    
    public function morningPlanner() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get departments
            $stmt = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get projects
            $stmt = $db->query("SELECT id, name, department_id FROM projects WHERE status = 'active' ORDER BY name");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get task categories
            $stmt = $db->query("SELECT id, name, department_id FROM task_categories ORDER BY name");
            $taskCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'departments' => $departments,
                'projects' => $projects,
                'task_categories' => $taskCategories,
                'active_page' => 'planner'
            ];
            
            $this->view('daily_workflow/morning_planner', $data);
        } catch (Exception $e) {
            error_log('Morning planner error: ' . $e->getMessage());
            $data = [
                'departments' => [],
                'projects' => [],
                'task_categories' => [],
                'active_page' => 'planner',
                'error' => 'Unable to load planner data'
            ];
            $this->view('daily_workflow/morning_planner', $data);
        }
    }
    
    public function getProjectsByDepartment() {
        AuthMiddleware::requireAuth();
        
        $departmentId = $_GET['department_id'] ?? null;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM projects WHERE department_id = ? AND status = 'active' ORDER BY name");
            $stmt->execute([$departmentId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['projects' => $projects]);
        } catch (Exception $e) {
            echo json_encode(['projects' => [], 'error' => 'Failed to load projects']);
        }
    }
    
    public function getTaskCategoriesByDepartment() {
        AuthMiddleware::requireAuth();
        
        $departmentId = $_GET['department_id'] ?? null;
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM task_categories WHERE department_id = ? ORDER BY name");
            $stmt->execute([$departmentId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['categories' => $categories]);
        } catch (Exception $e) {
            echo json_encode(['categories' => [], 'error' => 'Failed to load categories']);
        }
    }
    
    public function addTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $taskData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'department_id' => intval($_POST['department_id'] ?? 0),
                    'project_id' => !empty($_POST['project_id']) ? intval($_POST['project_id']) : null,
                    'category_id' => intval($_POST['category_id'] ?? 0),
                    'assigned_to' => $_SESSION['user_id'],
                    'planned_date' => $_POST['planned_date'] ?? date('Y-m-d'),
                    'priority' => $_POST['priority'] ?? 'medium',
                    'estimated_hours' => floatval($_POST['estimated_hours'] ?? 1)
                ];
                
                $sql = "INSERT INTO daily_tasks (title, description, department_id, project_id, category_id, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned', NOW())";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $taskData['title'],
                    $taskData['description'],
                    $taskData['department_id'],
                    $taskData['project_id'],
                    $taskData['category_id'],
                    $taskData['assigned_to'],
                    $taskData['planned_date'],
                    $taskData['priority'],
                    $taskData['estimated_hours']
                ]);
                
                echo json_encode(['success' => $result, 'task_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                error_log('Add task error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to add task']);
            }
        }
    }
}
?>