<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';

class LeaveController {
    private $db;
    private $leave;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->leave = new Leave();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        if ($role === 'User') {
            $leaves = $this->leave->getByUserId($user_id);
        } else {
            $leaves = $this->leave->getAll();
        }
        
        $data = ['leaves' => $leaves];
        include __DIR__ . '/../views/leaves/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $data = [
                'user_id' => $userId,
                'type' => $_POST['type'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => $_POST['reason']
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
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
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];
        
        $data = [
            'user_id' => $userId,
            'type' => $input['type'] ?? '',
            'start_date' => $input['start_date'] ?? '',
            'end_date' => $input['end_date'] ?? '',
            'reason' => $input['reason'] ?? ''
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