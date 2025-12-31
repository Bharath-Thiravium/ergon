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
    
    public function view($id) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            $stmt = $pdo->prepare("
                SELECT f.*, c.name as contact_name, c.company as contact_company, c.phone as contact_phone,
                       u.name as user_name, t.title as task_title
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN tasks t ON f.task_id = t.id
                WHERE f.id = ?
            ");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $data = ['followup' => $followup, 'active_page' => 'followups'];
        } catch (Exception $e) {
            error_log('Followup view error: ' . $e->getMessage());
            $data = ['followup' => [], 'active_page' => 'followups', 'error' => $e->getMessage()];
        }
        
        $this->view('followups/view', $data);
    }
    
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            $stmt = $pdo->prepare("
                SELECT f.*, c.name as contact_name, c.company as contact_company,
                       u.name as user_name, t.title as task_title
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN tasks t ON f.task_id = t.id
                WHERE f.id = ?
            ");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            // Get contacts and tasks for dropdowns
            $contacts = $pdo->query("SELECT * FROM contacts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $tasks = $pdo->query("SELECT id, title, description, deadline as due_date FROM tasks WHERE status != 'completed' ORDER BY deadline ASC")->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'followup' => $followup,
                'contacts' => $contacts,
                'tasks' => $tasks,
                'active_page' => 'followups'
            ];
        } catch (Exception $e) {
            error_log('Followup edit error: ' . $e->getMessage());
            $data = [
                'followup' => [],
                'contacts' => [],
                'tasks' => [],
                'active_page' => 'followups',
                'error' => $e->getMessage()
            ];
        }
        
        $this->view('followups/edit', $data);
    }
    
    public function update($id) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $followup_type = $_POST['followup_type'] ?? 'standalone';
            $task_id = !empty($_POST['task_id']) ? intval($_POST['task_id']) : null;
            $contact_id = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;
            $follow_up_date = $_POST['follow_up_date'] ?? date('Y-m-d');
            
            if (empty($title)) {
                header('Location: /ergon/followups/edit/' . $id . '?error=Title is required');
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE followups SET contact_id = ?, task_id = ?, title = ?, description = ?, followup_type = ?, follow_up_date = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $contact_id,
                $task_id,
                $title,
                $description,
                $followup_type,
                $follow_up_date,
                $id
            ]);
            
            if ($result) {
                header('Location: /ergon/followups?success=Follow-up updated successfully');
            } else {
                header('Location: /ergon/followups/edit/' . $id . '?error=Failed to update follow-up');
            }
            exit;
        } catch (Exception $e) {
            error_log('Followup update error: ' . $e->getMessage());
            header('Location: /ergon/followups/edit/' . $id . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function complete($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::connect();
            
            // Get followup details including task_id
            $stmt = $pdo->prepare("SELECT id, contact_id, task_id, status FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                // Complete the followup
                $stmt = $pdo->prepare("UPDATE followups SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    // Log history
                    $this->logFollowupHistory($pdo, $id, 'completed', $followup['status'], 'Follow-up completed');
                    
                    // If this followup is linked to a task, update the task status and sync with Daily Planner
                    if ($followup['task_id']) {
                        $this->updateLinkedTaskWithPlannerSync($pdo, $followup['task_id'], 'completed');
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Follow-up completed successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to complete follow-up']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
            }
        } catch (Exception $e) {
            error_log('Followup complete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to complete follow-up']);
        }
        exit;
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
    
    /**
     * Update linked task status with Daily Planner sync
     */
    private function updateLinkedTaskWithPlannerSync($pdo, $taskId, $status) {
        try {
            $stmt = $pdo->prepare("SELECT status, progress FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                $oldStatus = $task['status'];
                $newProgress = ($status === 'completed') ? 100 : $task['progress'];
                
                // Update task status
                $stmt = $pdo->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$status, $newProgress, $taskId]);
                
                if ($result) {
                    error_log("Follow-up completion: Updated task {$taskId} status from {$oldStatus} to {$status}");
                    
                    // Sync with Daily Planner
                    $this->syncTaskWithDailyPlanner($pdo, $taskId, $status, $newProgress);
                }
            }
        } catch (Exception $e) {
            error_log('Update linked task with planner sync error: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync task changes with Daily Planner
     */
    private function syncTaskWithDailyPlanner($pdo, $taskId, $status, $progress) {
        try {
            // Update all daily_tasks entries that reference this task
            $stmt = $pdo->prepare("
                UPDATE daily_tasks 
                SET status = ?, completed_percentage = ?, 
                    completion_time = CASE WHEN ? = 'completed' THEN NOW() ELSE completion_time END,
                    updated_at = NOW()
                WHERE original_task_id = ? OR task_id = ?
            ");
            $result = $stmt->execute([$status, $progress, $status, $taskId, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Follow-up completion: Successfully synced task {$taskId} with Daily Planner - {$stmt->rowCount()} entries updated");
            } else {
                error_log("Follow-up completion: No Daily Planner entries found for task {$taskId}");
            }
        } catch (Exception $e) {
            error_log('Sync task with Daily Planner error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log followup history
     */
    private function logFollowupHistory($pdo, $followupId, $action, $oldValue = null, $notes = null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([$followupId, $action, $oldValue, $notes, $_SESSION['user_id'] ?? null]);
        } catch (Exception $e) {
            error_log('Followup history log error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function ensureTablesExist($pdo) {
        try {
            // Create contacts table
            DatabaseHelper::safeExec($pdo, "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
            // Create followups table
            DatabaseHelper::safeExec($pdo, "CREATE TABLE IF NOT EXISTS followups (
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
            )", "Create table");
            
            // Create followup_history table
            DatabaseHelper::safeExec($pdo, "CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id)
            )", "Create table");
        } catch (Exception $e) {
            error_log('ensureTablesExist error: ' . $e->getMessage());
        }
    }
}
?>
