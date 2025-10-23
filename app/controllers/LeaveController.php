<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Leave.php';

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
            $data = [
                'user_id' => $_SESSION['user_id'],
                'type' => $_POST['type'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => $_POST['reason']
            ];
            
            if ($this->leave->create($data)) {
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
            $this->leave->updateStatus($id, 'Approved', 2);
        }
        header('Location: /ergon/leaves');
        exit;
    }
    
    public function reject($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
            $this->leave->updateStatus($id, 'Rejected', 2);
        }
        header('Location: /ergon/leaves');
        exit;
    }
}