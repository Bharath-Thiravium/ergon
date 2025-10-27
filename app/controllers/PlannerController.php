<?php

class PlannerController extends Controller {
    
    public function calendar() {
        $this->requireAuth();
        
        $title = 'Daily Planner';
        $active_page = 'planner';
        
        $data = [
            'plans' => [
                ['id' => 1, 'title' => 'Team Meeting', 'description' => 'Weekly team sync', 'plan_date' => '2024-01-20 10:00:00'],
                ['id' => 2, 'title' => 'Project Review', 'description' => 'Review project progress', 'plan_date' => '2024-01-21 14:00:00']
            ]
        ];
        
        include __DIR__ . '/../../views/planner/calendar.php';
    }
    
    public function create() {
        $this->requireAuth();
        
        $title = 'Create Plan';
        $active_page = 'planner';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO daily_planner (user_id, plan_date, title, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $_POST['plan_date'], $_POST['title'], $_POST['description']]);
                
                $this->redirect('/ergon/planner/calendar');
            } catch (Exception $e) {
                $this->handleError($e, 'Failed to create plan');
            }
        }
        
        // Load categories and departments
        $db = Database::connect();
        
        // Get user's departments
        $stmt = $db->prepare("SELECT d.* FROM departments d JOIN users u ON FIND_IN_SET(d.name, u.department) WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $departments = $stmt->fetchAll();
        
        // Get categories for user's departments
        $categories = [];
        if (!empty($departments)) {
            $deptNames = array_column($departments, 'name');
            $placeholders = str_repeat('?,', count($deptNames) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM task_categories WHERE department_name IN ($placeholders) AND is_active = 1 ORDER BY category_name");
            $stmt->execute($deptNames);
            $categories = $stmt->fetchAll();
        }
        
        $data = [
            'departments' => $departments,
            'categories' => $categories
        ];
        
        ob_start();
        include __DIR__ . '/../../views/planner/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../../views/layouts/dashboard.php';
    }
    
    public function store() {
        $this->create();
    }
    
    public function update() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE daily_planner SET title = ?, description = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['id'], $_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
    
    public function getDepartmentForm() {
        $this->requireAuth(['admin', 'owner']);
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM departments WHERE status = 'active'");
            $stmt->execute();
            $departments = $stmt->fetchAll();
            
            echo json_encode(['departments' => $departments]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getPlansForDate() {
        $this->requireAuth();
        
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM daily_planner WHERE DATE(plan_date) = ? ORDER BY created_at ASC");
            $stmt->execute([$date]);
            $plans = $stmt->fetchAll();
            
            echo json_encode(['plans' => $plans]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
