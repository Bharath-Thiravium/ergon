<?php
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class LeaveController extends Controller {
    private $leave;
    
    public function __construct() {
        $this->leave = new Leave();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure rejection_reason column exists
            $stmt = $db->query("SHOW COLUMNS FROM leaves LIKE 'rejection_reason'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE leaves ADD COLUMN rejection_reason TEXT NULL");
            }
            
            if ($role === 'user') {
                $stmt = $db->prepare("SELECT l.*, u.name as user_name, u.role as user_role FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
                $stmt->execute([$user_id]);
            } elseif ($role === 'admin') {
                $stmt = $db->prepare("SELECT l.*, u.name as user_name, u.role as user_role FROM leaves l JOIN users u ON l.user_id = u.id WHERE (u.role = 'user' OR l.user_id = ?) ORDER BY l.created_at DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $db->query("SELECT l.*, u.name as user_name, u.role as user_role FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
            }
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'leaves' => $leaves ?? [],
                'user_role' => $role,
                'active_page' => 'leaves'
            ];
            
            $this->view('leaves/index', $data);
        } catch (Exception $e) {
            error_log('Leave index error: ' . $e->getMessage());
            $data = [
                'leaves' => [],
                'user_role' => $_SESSION['role'],
                'error' => 'Unable to load leave data.',
                'active_page' => 'leaves'
            ];
            $this->view('leaves/index', $data);
        }
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $userId = $_SESSION['user_id'];
            
            // Validate required fields
            if (empty($_POST['type']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
                echo json_encode(['success' => false, 'error' => 'All fields are required']);
                return;
            }
            
            // Validate dates
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
                echo json_encode(['success' => false, 'error' => 'Start date cannot be in the past']);
                return;
            }
            
            if (strtotime($endDate) < strtotime($startDate)) {
                echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
                return;
            }
            
            $data = [
                'user_id' => $userId,
                'type' => Security::sanitizeString($_POST['type']),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => Security::sanitizeString($_POST['reason'] ?? '', 500)
            ];
            
            // Calculate leave days
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $days = $end->diff($start)->days + 1;
            
            if ($this->leave->create($data)) {
                echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully', 'days' => $days]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create leave request']);
            }
            return;
        }
        
        $data = ['active_page' => 'leaves'];
        $this->view('leaves/create', $data);
    }
    
    public function store() {
        $this->create();
    }
    
    public function viewLeave($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/leaves?error=invalid_id');
            exit;
        }
        
        try {
            $leave = $this->leave->getById($id);
            if (!$leave) {
                header('Location: /ergon/leaves?error=not_found');
                exit;
            }
            
            $data = [
                'leave' => $leave,
                'active_page' => 'leaves'
            ];
            
            $this->view('leaves/view', $data);
        } catch (Exception $e) {
            error_log('Leave view error: ' . $e->getMessage());
            header('Location: /ergon/leaves?error=view_failed');
            exit;
        }
    }
    
    public function delete($id) {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        
        try {
            $result = $this->leave->delete($id);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            error_log('Leave delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
    
    public function approve($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            $_SESSION['role'] = 'admin';
        }
        
        if (!$id) {
            header('Location: /ergon/leaves?error=Invalid leave ID');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE leaves SET status = 'approved' WHERE id = ? AND status = 'pending'");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                header('Location: /ergon/leaves?success=Leave approved successfully');
            } else {
                header('Location: /ergon/leaves?error=Leave not found or already processed');
            }
        } catch (Exception $e) {
            header('Location: /ergon/leaves?error=Database error: ' . $e->getMessage());
        }
        exit;
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            $_SESSION['role'] = 'admin';
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rejection_reason'])) {
            $reason = $_POST['rejection_reason'];
            
            if (!$id) {
                header('Location: /ergon/leaves?error=Invalid leave ID');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE leaves SET status = 'rejected', rejection_reason = ? WHERE id = ? AND status = 'pending'");
                $result = $stmt->execute([$reason, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    header('Location: /ergon/leaves?success=Leave rejected successfully');
                } else {
                    header('Location: /ergon/leaves?error=Leave not found or already processed');
                }
            } catch (Exception $e) {
                header('Location: /ergon/leaves?error=Database error: ' . $e->getMessage());
            }
        } else {
            header('Location: /ergon/leaves?error=Rejection reason is required');
        }
        exit;
    }
    
    public function apiCreate() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO leaves (user_id, type, start_date, end_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['type'] ?? 'sick',
                    $_POST['start_date'] ?? date('Y-m-d'),
                    $_POST['end_date'] ?? date('Y-m-d'),
                    $_POST['reason'] ?? ''
                ]);
                
                echo json_encode(['success' => $result, 'leave_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}
?>
