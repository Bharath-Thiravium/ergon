<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';

class ExpenseController {
    private $db;
    private $expense;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->expense = new Expense();
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
            $expenses = $this->expense->getByUserId($user_id);
        } else {
            $expenses = $this->expense->getAll();
        }
        
        $data = ['expenses' => $expenses];
        include __DIR__ . '/../views/expenses/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $attachment = null;
            
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
                $uploadDir = __DIR__ . '/../../storage/receipts/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $filename = time() . '_' . $_FILES['receipt']['name'];
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
                    $attachment = $filename;
                }
            }
            
            $data = [
                'user_id' => $_SESSION['user_id'],
                'category' => $_POST['category'],
                'amount' => $_POST['amount'],
                'description' => $_POST['description'],
                'date' => $_POST['date'],
                'attachment' => $attachment
            ];
            
            if ($this->expense->create($data)) {
                NotificationHelper::notifyAdmins(
                    'New Expense Request',
                    "Expense of ₹{$_POST['amount']} submitted by user #{$_SESSION['user_id']}",
                    '/ergon/expenses'
                );
                header('Location: /ergon/user/requests?success=1');
                exit;
            }
        }
        
        include __DIR__ . '/../views/expenses/create.php';
    }
    
    public function approve($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
            $this->expense->updateStatus($id, 'approved', $_SESSION['user_id']);
            $expense = $this->expense->getById($id);
            if ($expense) {
                NotificationHelper::notifyUser(
                    $expense['user_id'],
                    'Expense Approved',
                    'Your expense request has been approved by admin.',
                    '/ergon/user/requests'
                );
            }
        }
        header('Location: /ergon/expenses');
        exit;
    }
    
    public function reject($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
            $this->expense->updateStatus($id, 'rejected', $_SESSION['user_id']);
            $expense = $this->expense->getById($id);
            if ($expense) {
                NotificationHelper::notifyUser(
                    $expense['user_id'],
                    'Expense Rejected',
                    'Your expense request has been rejected by admin.',
                    '/ergon/user/requests'
                );
            }
        }
        header('Location: /ergon/expenses');
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
            'category' => $input['category'] ?? '',
            'amount' => $input['amount'] ?? 0,
            'description' => $input['description'] ?? '',
            'date' => $input['date'] ?? date('Y-m-d')
        ];
        
        if ($this->expense->create($data)) {
            NotificationHelper::notifyAdmins(
                'New Expense Request',
                "Expense of ₹{$data['amount']} submitted by user #{$userId}",
                '/ergon/expenses'
            );
            echo json_encode(['success' => true, 'message' => 'Expense request submitted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit expense request']);
        }
    }
}