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
            
            if ($role === 'user') {
                $leaves = $this->leave->getByUserId($user_id);
            } else {
                $leaves = $this->leave->getAll();
            }
            
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
            $userId = $_SESSION['user_id'];
            
            // Validate required fields
            if (empty($_POST['type']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
                $data = ['error' => 'All fields are required', 'active_page' => 'leaves'];
                $this->view('leaves/create', $data);
                return;
            }
            
            // Validate dates
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
                $data = ['error' => 'Start date cannot be in the past', 'active_page' => 'leaves'];
                $this->view('leaves/create', $data);
                return;
            }
            
            if (strtotime($endDate) < strtotime($startDate)) {
                $data = ['error' => 'End date must be after start date', 'active_page' => 'leaves'];
                $this->view('leaves/create', $data);
                return;
            }
            
            $data = [
                'user_id' => $userId,
                'type' => Security::sanitizeString($_POST['type']),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => Security::sanitizeString($_POST['reason'] ?? '', 500)
            ];
            
            if ($this->leave->create($data)) {
                header('Location: /ergon/leaves?success=1');
                exit;
            } else {
                $data = ['error' => 'Failed to create leave request', 'active_page' => 'leaves'];
                $this->view('leaves/create', $data);
                return;
            }
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
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['leave_id'] ?? $id;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/leaves?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE leaves SET status = 'approved', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/leaves?success=Leave approved successfully');
            } else {
                header('Location: /ergon/leaves?error=Failed to approve leave');
            }
        } catch (Exception $e) {
            error_log('Leave approval error: ' . $e->getMessage());
            header('Location: /ergon/leaves?error=approval_failed');
        }
        exit;
    }
    
    public function reject($id = null) {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['leave_id'] ?? $id;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/leaves?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE leaves SET status = 'rejected', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/leaves?success=Leave rejected successfully');
            } else {
                header('Location: /ergon/leaves?error=Failed to reject leave');
            }
        } catch (Exception $e) {
            error_log('Leave rejection error: ' . $e->getMessage());
            header('Location: /ergon/leaves?error=rejection_failed');
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
