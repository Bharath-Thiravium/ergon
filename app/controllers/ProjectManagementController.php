<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';

class ProjectManagementController extends Controller {
    
    public function index() {
        // Check authentication
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
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
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
            // Add columns if they don't exist
            try {
                DatabaseHelper::safeExec($db, "ALTER TABLE projects ADD COLUMN department_id INT NULL", "Alter table");
            } catch (Exception $e) {}
            try {
                DatabaseHelper::safeExec($db, "ALTER TABLE projects ADD COLUMN latitude DECIMAL(10, 8) NULL", "Alter table");
            } catch (Exception $e) {}
            try {
                DatabaseHelper::safeExec($db, "ALTER TABLE projects ADD COLUMN longitude DECIMAL(11, 8) NULL", "Alter table");
            } catch (Exception $e) {}
            try {
                DatabaseHelper::safeExec($db, "ALTER TABLE projects ADD COLUMN checkin_radius INT DEFAULT 100", "Alter table");
            } catch (Exception $e) {}
            try {
                $db->exec("ALTER TABLE projects ADD COLUMN place VARCHAR(255) NULL");
            } catch (Exception $e) {}
            try {
                $db->exec("ALTER TABLE projects ADD COLUMN budget DECIMAL(15,2) NULL");
            } catch (Exception $e) {}
            
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
            error_log('Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo "Error loading project management: " . $e->getMessage();
        }
    }
    
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
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
            
            $stmt = $db->prepare("INSERT INTO projects (name, description, place, budget, latitude, longitude, checkin_radius, department_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['place'] ?? '',
                !empty($_POST['budget']) ? $_POST['budget'] : null,
                !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                !empty($_POST['checkin_radius']) ? $_POST['checkin_radius'] : 100,
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
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
        
        if (empty($_POST['project_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Project ID is required']);
            return;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, place = ?, budget = ?, latitude = ?, longitude = ?, checkin_radius = ?, department_id = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['place'] ?? '',
                !empty($_POST['budget']) ? $_POST['budget'] : null,
                !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                !empty($_POST['checkin_radius']) ? $_POST['checkin_radius'] : 100,
                !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                $_POST['status'] ?? 'active',
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
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
