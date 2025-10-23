<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Expense.php';

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
            $this->expense->updateStatus($id, 'approved', 2);
        }
        header('Location: /ergon/expenses');
        exit;
    }
    
    public function reject($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
            $this->expense->updateStatus($id, 'rejected', 2);
        }
        header('Location: /ergon/expenses');
        exit;
    }
}