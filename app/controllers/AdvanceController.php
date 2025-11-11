<?php
require_once __DIR__ . '/../core/Controller.php';

class AdvanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'] ?? 'user';
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure table exists
            $db->exec("CREATE TABLE IF NOT EXISTS advances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) DEFAULT 'General Advance',
                amount DECIMAL(10,2) NOT NULL,
                reason TEXT NOT NULL,
                requested_date DATE NULL,
                repayment_months INT DEFAULT 1,
                status VARCHAR(20) DEFAULT 'pending',
                approved_by INT NULL,
                approved_at DATETIME NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            if ($role === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $db->query("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
            }
            $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('advances/index', ['advances' => $advances, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance index error: ' . $e->getMessage());
            $this->view('advances/index', ['advances' => [], 'error' => 'Unable to load advances', 'active_page' => 'advances']);
        }
    }
    
    public function create() {
        $this->requireAuth();
        $this->view('advances/create');
    }
    
    public function store() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO advances (user_id, type, amount, reason, requested_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    trim($_POST['type'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    trim($_POST['reason'] ?? ''),
                    date('Y-m-d')
                ]);
                
                if ($result) {
                    header('Location: /ergon/advances?success=1');
                } else {
                    header('Location: /ergon/advances/create?error=1');
                }
                exit;
            } catch (Exception $e) {
                error_log('Advance store error: ' . $e->getMessage());
                header('Location: /ergon/advances/create?error=1');
                exit;
            }
        }
        
        header('Location: /ergon/advances/create');
        exit;
    }
    
    public function approve($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!$id) {
            header('Location: /ergon/advances?error=Invalid advance ID');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE advances SET status = 'approved' WHERE id = ? AND status = 'pending'");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                header('Location: /ergon/advances?success=Advance approved successfully');
            } else {
                header('Location: /ergon/advances?error=Advance not found or already processed');
            }
        } catch (Exception $e) {
            header('Location: /ergon/advances?error=Database error: ' . $e->getMessage());
        }
        exit;
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rejection_reason'])) {
            $reason = $_POST['rejection_reason'];
            
            if (!$id) {
                header('Location: /ergon/advances?error=Invalid advance ID');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ? WHERE id = ? AND status = 'pending'");
                $result = $stmt->execute([$reason, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    header('Location: /ergon/advances?success=Advance rejected successfully');
                } else {
                    header('Location: /ergon/advances?error=Advance not found or already processed');
                }
            } catch (Exception $e) {
                header('Location: /ergon/advances?error=Database error: ' . $e->getMessage());
            }
        } else {
            header('Location: /ergon/advances?error=Rejection reason is required');
        }
        exit;
    }
    
    public function viewAdvance($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if ($_SESSION['role'] === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
                $stmt->execute([$id]);
            }
            
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: /ergon/advances?error=not_found');
                exit;
            }
            
            $this->view('advances/view', ['advance' => $advance, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance view error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=1');
            exit;
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM advances WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        exit;
    }
}
?>