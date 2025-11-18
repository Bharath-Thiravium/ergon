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
            $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $followups = $pdo->query("
                SELECT f.*, c.name as contact_name, c.company as contact_company 
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                ORDER BY f.follow_up_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $data = ['followups' => $followups, 'active_page' => 'followups'];
        } catch (Exception $e) {
            $data = ['followups' => [], 'active_page' => 'followups', 'error' => $e->getMessage()];
        }
        
        $this->view('followups/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        $data = ['active_page' => 'followups'];
        $this->view('followups/create', $data);
    }
    
    public function store() {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Create tables if they don't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                email VARCHAR(255),
                company VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                followup_type ENUM('standalone', 'task') DEFAULT 'standalone',
                task_id INT NULL,
                contact_id INT NOT NULL,
                follow_up_date DATE NOT NULL,
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $stmt = $pdo->prepare("INSERT INTO followups (title, description, followup_type, task_id, contact_id, follow_up_date) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $_POST['title'],
                $_POST['description'] ?? null,
                $_POST['followup_type'],
                $_POST['task_id'] ?: null,
                $_POST['contact_id'],
                $_POST['follow_up_date']
            ]);
            
            if ($result) {
                header('Location: /ergon/followups?success=Created');
            } else {
                header('Location: /ergon/followups/create?error=Failed to create');
            }
            exit;
        } catch (Exception $e) {
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
        echo json_encode(['success' => true]);
        exit;
    }
}
?>