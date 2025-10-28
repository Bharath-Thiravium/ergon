<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Advance.php';

class AdvanceController extends Controller {
    private $advanceModel;
    
    public function __construct() {
        $this->advanceModel = new Advance();
    }
    
    public function index() {
        $this->requireAuth();
        $advances = $this->advanceModel->getAll();
        $this->view('advances/index', ['advances' => $advances]);
    }
    
    public function create() {
        $this->requireAuth();
        $this->view('advances/create');
    }
    
    public function store() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $amount = floatval($_POST['amount'] ?? 0);
                $reason = trim($_POST['reason'] ?? '');
                
                if ($amount <= 0) {
                    header('Location: /ergon/advances/create?error=Invalid amount');
                    exit;
                }
                
                if (empty($reason)) {
                    header('Location: /ergon/advances/create?error=Reason is required');
                    exit;
                }
                
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO advances (user_id, amount, reason, requested_date, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $amount,
                    $reason,
                    date('Y-m-d')
                ]);
                
                if ($result) {
                    // Try to log activity, but don't fail if it doesn't work
                    try {
                        require_once __DIR__ . '/../models/ActivityLog.php';
                        $activityLog = new ActivityLog();
                        $activityLog->log($_SESSION['user_id'], 'advance_request', "Requested advance of $amount");
                    } catch (Exception $logError) {
                        error_log('Activity log error: ' . $logError->getMessage());
                    }
                    
                    header('Location: /ergon/advances?success=Advance request submitted successfully');
                } else {
                    header('Location: /ergon/advances/create?error=Failed to submit advance request');
                }
                exit;
            } catch (Exception $e) {
                error_log('Advance creation error: ' . $e->getMessage());
                header('Location: /ergon/advances/create?error=Failed to submit advance request');
                exit;
            }
        }
        
        header('Location: /ergon/advances/create');
        exit;
    }
    
    public function approve($id = null) {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['advance_id'] ?? $id;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/advances?success=Advance approved successfully');
            } else {
                header('Location: /ergon/advances?error=Failed to approve advance');
            }
        } catch (Exception $e) {
            header('Location: /ergon/advances?error=Approval failed');
        }
        exit;
    }
    
    public function reject($id = null) {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['advance_id'] ?? $id;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE advances SET status = 'rejected', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/advances?success=Advance rejected successfully');
            } else {
                header('Location: /ergon/advances?error=Failed to reject advance');
            }
        } catch (Exception $e) {
            header('Location: /ergon/advances?error=Rejection failed');
        }
        exit;
    }
    
    public function view($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
            $stmt->execute([$id]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: /ergon/advances?error=Advance not found');
                exit;
            }
            
            $data = [
                'advance' => $advance,
                'active_page' => 'advances'
            ];
            
            $this->view('advances/view', $data);
        } catch (Exception $e) {
            error_log('Advance view error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=Failed to load advance');
            exit;
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM advances WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            error_log('Advance delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
}
?>
