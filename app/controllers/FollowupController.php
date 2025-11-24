<?php
require_once __DIR__ . '/../core/Controller.php';

class FollowupController extends Controller {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function index() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            // Ensure tables exist
            $this->ensureTablesExist($pdo);
            
            $sql = "
                SELECT f.*, c.name as contact_name, c.company as contact_company, c.phone as contact_phone,
                       u.name as user_name
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN tasks t ON f.task_id = t.id
                WHERE (f.task_id IS NULL OR t.id IS NOT NULL)
            ";
            
            // Filter by user if not admin/owner
            if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $sql .= " AND f.user_id = ?";
                $stmt = $pdo->prepare($sql . " ORDER BY f.follow_up_date DESC, f.created_at DESC");
                $stmt->execute([$_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare($sql . " ORDER BY f.follow_up_date DESC, f.created_at DESC");
                $stmt->execute();
            }
            
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = ['followups' => $followups, 'active_page' => 'followups'];
        } catch (Exception $e) {
            error_log('Followup index error: ' . $e->getMessage());
            $data = ['followups' => [], 'active_page' => 'followups', 'error' => $e->getMessage()];
        }
        
        $this->view('followups/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            // Ensure tables exist
            $this->ensureTablesExist($pdo);
            
            // Get contacts for dropdown
            $contacts = $pdo->query("SELECT * FROM contacts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            
            // Get tasks for dropdown
            $tasks = $pdo->query("SELECT id, title, description, deadline as due_date FROM tasks WHERE status != 'completed' ORDER BY deadline ASC")->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'active_page' => 'followups',
                'contacts' => $contacts,
                'tasks' => $tasks
            ];
        } catch (Exception $e) {
            error_log('Followup create error: ' . $e->getMessage());
            $data = [
                'active_page' => 'followups',
                'contacts' => [],
                'tasks' => [],
                'error' => $e->getMessage()
            ];
        }
        
        $this->view('followups/create', $data);
    }
    
    public function store() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            // Ensure tables exist with proper structure
            $this->ensureTablesExist($pdo);
            
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $followup_type = $_POST['followup_type'] ?? 'standalone';
            $task_id = !empty($_POST['task_id']) ? intval($_POST['task_id']) : null;
            $contact_id = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;
            $follow_up_date = $_POST['follow_up_date'] ?? date('Y-m-d');
            $user_id = $_SESSION['user_id'] ?? 1;
            
            if (empty($title)) {
                header('Location: /ergon/followups/create?error=Title is required');
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO followups (user_id, contact_id, task_id, title, description, followup_type, follow_up_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
            $result = $stmt->execute([
                $user_id,
                $contact_id,
                $task_id,
                $title,
                $description,
                $followup_type,
                $follow_up_date
            ]);
            
            if ($result) {
                $followup_id = $pdo->lastInsertId();
                
                // Log creation in history
                $stmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, notes, created_by, created_at) VALUES (?, 'created', 'Follow-up created', ?, NOW())");
                $stmt->execute([$followup_id, $user_id]);
                
                header('Location: /ergon/followups?success=Follow-up created successfully');
            } else {
                header('Location: /ergon/followups/create?error=Failed to create follow-up');
            }
            exit;
        } catch (Exception $e) {
            error_log('Followup store error: ' . $e->getMessage());
            header('Location: /ergon/followups/create?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function viewFollowup($id) {
        $data = ['followup' => [], 'active_page' => 'followups'];
        $this->view('followups/view', $data);
    }
    
    public function delete($id) {
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            $stmt = $pdo->prepare("DELETE FROM followups WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Follow-up deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete follow-up']);
            }
        } catch (Exception $e) {
            error_log('Followup delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
        exit;
    }
    
    private function ensureTablesExist($pdo) {
        try {
            // Create contacts table
            $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Create followups table
            $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                contact_id INT,
                task_id INT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                followup_type ENUM('standalone', 'task') DEFAULT 'standalone',
                follow_up_date DATE NOT NULL,
                status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_contact_id (contact_id),
                INDEX idx_task_id (task_id),
                INDEX idx_follow_up_date (follow_up_date),
                INDEX idx_status (status)
            )");
            
            // Create followup_history table
            $pdo->exec("CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id)
            )");
        } catch (Exception $e) {
            error_log('ensureTablesExist error: ' . $e->getMessage());
        }
    }
}
?>