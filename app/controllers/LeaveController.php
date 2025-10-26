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
                header('Location: /ergon/public/leaves?success=1');
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
    
    public function approve($id) {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/public/leaves?error=invalid_id');
            exit;
        }
        
        try {
            $this->leave->updateStatus($id, 'approved', $_SESSION['user_id']);
            header('Location: /ergon/public/leaves?success=approved');
        } catch (Exception $e) {
            error_log('Leave approval error: ' . $e->getMessage());
            header('Location: /ergon/public/leaves?error=approval_failed');
        }
        exit;
    }
    
    public function reject($id) {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/public/leaves?error=invalid_id');
            exit;
        }
        
        try {
            $this->leave->updateStatus($id, 'rejected', $_SESSION['user_id']);
            header('Location: /ergon/public/leaves?success=rejected');
        } catch (Exception $e) {
            error_log('Leave rejection error: ' . $e->getMessage());
            header('Location: /ergon/public/leaves?error=rejection_failed');
        }
        exit;
    }
}
?>
