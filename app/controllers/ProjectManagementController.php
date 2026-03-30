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
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(11,8) NULL,
                checkin_radius INT DEFAULT 100,
                place VARCHAR(255) NULL,
                budget DECIMAL(15,2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");

            // Create pivot table for multiple departments
            $db->exec("CREATE TABLE IF NOT EXISTS project_departments (
                project_id INT NOT NULL,
                department_id INT NOT NULL,
                PRIMARY KEY (project_id, department_id)
            )");
            
            // Get all projects
            $stmt = $db->prepare("SELECT * FROM projects ORDER BY created_at DESC");
            $stmt->execute();
            $projects = $stmt->fetchAll();

            // Fetch all project-department mappings in ONE query
            $allDepts = [];
            if (!empty($projects)) {
                $stmt = $db->query("SELECT pd.project_id, d.id, d.name FROM project_departments pd JOIN departments d ON d.id = pd.department_id");
                foreach ($stmt->fetchAll() as $row) {
                    $allDepts[$row['project_id']][] = ['id' => $row['id'], 'name' => $row['name']];
                }
            }
            foreach ($projects as &$project) {
                $project['departments'] = $allDepts[$project['id']] ?? [];
            }
            unset($project);
            
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
            
            $stmt = $db->prepare("INSERT INTO projects (name, description, place, budget, latitude, longitude, checkin_radius, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['place'] ?? '',
                !empty($_POST['budget']) ? $_POST['budget'] : null,
                !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                !empty($_POST['checkin_radius']) ? $_POST['checkin_radius'] : 100
            ]);

            $projectId = $db->lastInsertId();

            // Save multiple departments in one query
            $deptIds = array_filter((array)($_POST['department_ids'] ?? []));
            if (!empty($deptIds)) {
                $placeholders = implode(',', array_fill(0, count($deptIds), '(?,?)'));
                $values = [];
                foreach ($deptIds as $deptId) {
                    $values[] = $projectId;
                    $values[] = $deptId;
                }
                $db->prepare("INSERT IGNORE INTO project_departments (project_id, department_id) VALUES $placeholders")->execute($values);
            }
            
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
            
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, place = ?, budget = ?, latitude = ?, longitude = ?, checkin_radius = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['place'] ?? '',
                !empty($_POST['budget']) ? $_POST['budget'] : null,
                !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                !empty($_POST['checkin_radius']) ? $_POST['checkin_radius'] : 100,
                $_POST['status'] ?? 'active',
                $_POST['project_id']
            ]);

            // Replace departments in one query
            $db->prepare("DELETE FROM project_departments WHERE project_id = ?")->execute([$_POST['project_id']]);
            $deptIds = array_filter((array)($_POST['department_ids'] ?? []));
            if (!empty($deptIds)) {
                $placeholders = implode(',', array_fill(0, count($deptIds), '(?,?)'));
                $values = [];
                foreach ($deptIds as $deptId) {
                    $values[] = $_POST['project_id'];
                    $values[] = $deptId;
                }
                $db->prepare("INSERT INTO project_departments (project_id, department_id) VALUES $placeholders")->execute($values);
            }
            
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
            
            $stmt = $db->prepare("DELETE FROM project_departments WHERE project_id = ?");
            $stmt->execute([$_POST['project_id']]);
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
