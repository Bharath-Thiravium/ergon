<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/SessionManager.php';

class LeaveController {
    private $db;
    private $leave;
    
    public function __construct() {
        SessionManager::start();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->leave = new Leave();
    }
    
    public function index() {
        SessionManager::requireLogin();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            if ($role === 'user') {
                $leaves = $this->leave->getByUserId($user_id);
            } else {
                $leaves = $this->leave->getAll();
            }
            
            $stats = $this->leave->getStats($role === 'user' ? $user_id : null);
            
            $data = [
                'leaves' => $leaves,
                'stats' => $stats,
                'user_role' => $role
            ];
            
            include __DIR__ . '/../views/leaves/index.php';
        } catch (Exception $e) {
            error_log('Leave index error: ' . $e->getMessage());
            $data = [
                'leaves' => [],
                'stats' => ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0],
                'user_role' => $_SESSION['role'],
                'error' => 'Unable to load leave data. Please try again.'
            ];
            include __DIR__ . '/../views/leaves/index.php';
        }
    }
    
    public function create() {
        SessionManager::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                die('CSRF validation failed');
            }
            
            $userId = $_SESSION['user_id'];
            $data = [
                'user_id' => $userId,
                'type' => Security::sanitizeString($_POST['type']),
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => Security::sanitizeString($_POST['reason'], 500)
            ];
            
            if ($this->leave->create($data)) {
                NotificationHelper::notifyAdmins(
                    'New Leave Request',
                    "Leave request from {$_POST['start_date']} to {$_POST['end_date']} by user #{$userId}",
                    '/ergon/leaves'
                );
                header('Location: /ergon/user/requests?success=1');
                exit;
            }
        }
        
        include __DIR__ . '/../views/leaves/create.php';
    }
    
    public function approve($id) {
        SessionManager::requireLogin();
        SessionManager::requireRole('admin');
        
        // Validate CSRF token
        if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        if ($_SESSION['role'] !== 'user') {
            $this->leave->updateStatus($id, 'Approved', $_SESSION['user_id']);
            $leave = $this->leave->getById($id);
            if ($leave) {
                NotificationHelper::notifyUser(
                    $leave['user_id'],
                    'Leave Approved',
                    'Your leave request has been approved by admin.',
                    '/ergon/user/requests'
                );
            }
        }
        header('Location: /ergon/leaves');
        exit;
    }
    
    public function reject($id) {
        SessionManager::requireLogin();
        SessionManager::requireRole('admin');
        
        // Validate CSRF token
        if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        if ($_SESSION['role'] !== 'user') {
            $this->leave->updateStatus($id, 'Rejected', $_SESSION['user_id']);
            $leave = $this->leave->getById($id);
            if ($leave) {
                NotificationHelper::notifyUser(
                    $leave['user_id'],
                    'Leave Rejected',
                    'Your leave request has been rejected by admin.',
                    '/ergon/user/requests'
                );
            }
        }
        header('Location: /ergon/leaves');
        exit;
    }
    
    public function apiCreate() {
        header('Content-Type: application/json');
        SessionManager::requireLogin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate CSRF token
        if (!Security::validateCSRFToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF validation failed']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        $data = [
            'user_id' => $userId,
            'type' => Security::sanitizeString($input['type'] ?? ''),
            'start_date' => $input['start_date'] ?? '',
            'end_date' => $input['end_date'] ?? '',
            'reason' => Security::sanitizeString($input['reason'] ?? '', 500)
        ];
        
        if ($this->leave->create($data)) {
            NotificationHelper::notifyAdmins(
                'New Leave Request',
                "Leave request from {$data['start_date']} to {$data['end_date']} by user #{$userId}",
                '/ergon/leaves'
            );
            echo json_encode(['success' => true, 'message' => 'Leave request submitted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit leave request']);
        }
    }
}