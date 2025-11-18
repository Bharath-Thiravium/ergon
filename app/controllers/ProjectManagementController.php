<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class ProjectManagementController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $title = 'Project Management';
        $active_page = 'project-management';
        
        try {
            $db = Database::connect();
            
            // Ensure projects table exists
            $db->exec("CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                department_id INT,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Get all projects with department info
            $stmt = $db->prepare("SELECT p.*, d.name as department_name FROM projects p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.created_at DESC");
            $stmt->execute();
            $projects = $stmt->fetchAll();
            
            // Get departments
            $stmt = $db->prepare("SELECT * FROM departments ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll();
            
            $data = [
                'projects' => $projects,
                'departments' => $departments
            ];
            
            include __DIR__ . '/../../views/admin/project_management.php';
            
        } catch (Exception $e) {
            error_log('Project management error: ' . $e->getMessage());
            http_response_code(500);
            echo "Error loading project management";
        }
    }
    
    public function create() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            // Ensure projects table exists
            $db->exec("CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                department_id INT,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $stmt = $db->prepare("INSERT INTO projects (name, description, department_id, status) VALUES (?, ?, ?, 'active')");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                !empty($_POST['department_id']) ? $_POST['department_id'] : null
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            error_log('Project creation error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function update() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, department_id = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                $_POST['status'],
                $_POST['project_id']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            error_log('Project update error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function delete() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute([$_POST['project_id']]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            error_log('Project deletion error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}