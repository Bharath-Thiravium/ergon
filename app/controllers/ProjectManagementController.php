<?php

class ProjectManagementController extends Controller {
    
    public function index() {
        $this->requireAuth(['admin', 'owner']);
        
        $title = 'Project Management';
        $active_page = 'project-management';
        
        try {
            $db = Database::connect();
            
            // Get all projects with department info
            $stmt = $db->prepare("SELECT p.*, d.name as department_name FROM projects p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.created_at DESC");
            $stmt->execute();
            $projects = $stmt->fetchAll();
            
            // Get departments
            $stmt = $db->prepare("SELECT * FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll();
            
            $data = [
                'projects' => $projects,
                'departments' => $departments
            ];
            
            ob_start();
            include __DIR__ . '/../../views/admin/project_management.php';
            $content = ob_get_clean();
            include __DIR__ . '/../../views/layouts/dashboard.php';
            
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to load project management');
        }
    }
    
    public function create() {
        $this->requireAuth(['admin', 'owner']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("INSERT INTO projects (name, description, department_id, status) VALUES (?, ?, ?, 'active')");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['department_id'] ?? null
            ]);
            
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function update() {
        $this->requireAuth(['admin', 'owner']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, department_id = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['department_id'] ?? null,
                $_POST['status'],
                $_POST['project_id']
            ]);
            
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function delete() {
        $this->requireAuth(['admin', 'owner']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute([$_POST['project_id']]);
            
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}