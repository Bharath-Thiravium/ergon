<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class PlannerController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $userId = $_SESSION['user_id'];
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensurePlannerTables($db);
            
            // Get today's planned tasks
            $plannedTasks = $this->getPlannedTasks($db, $userId, $date);
            
            // Get available assigned tasks not yet planned
            $availableTasks = $this->getAvailableAssignedTasks($db, $userId, $date);
            
            $data = [
                'planned_tasks' => $plannedTasks,
                'available_tasks' => $availableTasks,
                'current_date' => $date,
                'active_page' => 'planner'
            ];
            
            $this->view('planner/index', $data);
        } catch (Exception $e) {
            error_log('Planner error: ' . $e->getMessage());
            $this->view('planner/index', ['planned_tasks' => [], 'available_tasks' => [], 'current_date' => $date, 'active_page' => 'planner']);
        }
    }
    
    public function addTask() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensurePlannerTables($db);
                
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'date' => $_POST['date'] ?? date('Y-m-d'),
                    'task_id' => !empty($_POST['task_id']) ? intval($_POST['task_id']) : null,
                    'task_type' => $_POST['task_type'] ?? 'personal',
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'planned_start_time' => !empty($_POST['planned_start_time']) ? $_POST['planned_start_time'] : null,
                    'planned_duration' => intval($_POST['planned_duration'] ?? 60),
                    'priority_order' => intval($_POST['priority_order'] ?? 1)
                ];
                
                if (empty($data['title'])) {
                    echo json_encode(['success' => false, 'error' => 'Title is required']);
                    exit;
                }
                
                $stmt = $db->prepare("INSERT INTO daily_planner (user_id, date, task_id, task_type, title, description, planned_start_time, planned_duration, priority_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([
                    $data['user_id'], 
                    $data['date'], 
                    $data['task_id'], 
                    $data['task_type'], 
                    $data['title'], 
                    $data['description'], 
                    $data['planned_start_time'], 
                    $data['planned_duration'], 
                    $data['priority_order']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                } else {
                    $errorInfo = $stmt->errorInfo();
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $errorInfo[2]]);
                }
            } catch (Exception $e) {
                error_log('Planner addTask error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    public function updateStatus() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $plannerId = intval($_POST['planner_id'] ?? 0);
                $status = $_POST['status'] ?? 'planned';
                
                if ($plannerId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid planner ID']);
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE daily_planner SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$status, $plannerId, $_SESSION['user_id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No rows updated']);
                }
            } catch (Exception $e) {
                error_log('Planner updateStatus error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    private function getPlannedTasks($db, $userId, $date) {
        $stmt = $db->prepare("
            SELECT dp.*, t.deadline, t.priority as task_priority 
            FROM daily_planner dp 
            LEFT JOIN tasks t ON dp.task_id = t.id 
            WHERE dp.user_id = ? AND dp.date = ? 
            ORDER BY dp.priority_order, dp.planned_start_time
        ");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAvailableAssignedTasks($db, $userId, $date) {
        $stmt = $db->prepare("
            SELECT t.* FROM tasks t 
            WHERE t.assigned_to = ? 
            AND t.status IN ('assigned', 'in_progress') 
            AND t.id NOT IN (
                SELECT COALESCE(dp.task_id, 0) FROM daily_planner dp 
                WHERE dp.user_id = ? AND dp.date = ? AND dp.task_id IS NOT NULL
            )
            ORDER BY t.priority DESC, t.deadline ASC
        ");
        $stmt->execute([$userId, $userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        $data = [
            'active_page' => 'planner',
            'current_date' => $_GET['date'] ?? date('Y-m-d')
        ];
        
        $this->view('planner/create', $data);
    }
    
    public function store() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $this->ensurePlannerTables($db);
                
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'date' => $_POST['date'] ?? date('Y-m-d'),
                    'task_type' => $_POST['task_type'] ?? 'personal',
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'planned_start_time' => !empty($_POST['planned_start_time']) ? $_POST['planned_start_time'] : null,
                    'planned_duration' => intval($_POST['planned_duration'] ?? 60),
                    'priority_order' => intval($_POST['priority_order'] ?? 1)
                ];
                
                if (empty($data['title'])) {
                    header('Location: /ergon/planner/create?error=title_required');
                    exit;
                }
                
                $stmt = $db->prepare("INSERT INTO daily_planner (user_id, date, task_type, title, description, planned_start_time, planned_duration, priority_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([
                    $data['user_id'], 
                    $data['date'], 
                    $data['task_type'], 
                    $data['title'], 
                    $data['description'], 
                    $data['planned_start_time'], 
                    $data['planned_duration'], 
                    $data['priority_order']
                ]);
                
                if ($result) {
                    header('Location: /ergon/planner?date=' . $data['date'] . '&success=1');
                } else {
                    header('Location: /ergon/planner/create?error=save_failed');
                }
            } catch (Exception $e) {
                error_log('Planner store error: ' . $e->getMessage());
                header('Location: /ergon/planner/create?error=database_error');
            }
            exit;
        }
    }
    
    public function update() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $id = intval($_POST['id'] ?? 0);
                $data = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'planned_start_time' => !empty($_POST['planned_start_time']) ? $_POST['planned_start_time'] : null,
                    'planned_duration' => intval($_POST['planned_duration'] ?? 60),
                    'status' => $_POST['status'] ?? 'planned'
                ];
                
                if ($id <= 0 || empty($data['title'])) {
                    header('Location: /ergon/planner?error=invalid_data');
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE daily_planner SET title = ?, description = ?, planned_start_time = ?, planned_duration = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    $data['title'], 
                    $data['description'], 
                    $data['planned_start_time'], 
                    $data['planned_duration'], 
                    $data['status'],
                    $id, 
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    header('Location: /ergon/planner?success=updated');
                } else {
                    header('Location: /ergon/planner?error=update_failed');
                }
            } catch (Exception $e) {
                error_log('Planner update error: ' . $e->getMessage());
                header('Location: /ergon/planner?error=database_error');
            }
            exit;
        }
    }
    
    private function ensurePlannerTables($db) {
        try {
            // Create daily_planner table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS daily_planner (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                date DATE NOT NULL,
                task_id INT DEFAULT NULL,
                task_type VARCHAR(50) DEFAULT 'personal',
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                planned_start_time TIME DEFAULT NULL,
                planned_duration INT DEFAULT 60,
                priority_order INT DEFAULT 1,
                status VARCHAR(20) DEFAULT 'planned',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, date),
                INDEX idx_task_id (task_id)
            )");
            return true;
        } catch (Exception $e) {
            error_log('Table creation error: ' . $e->getMessage());
            return false;
        }
    }
}
?>