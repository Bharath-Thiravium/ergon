<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class ExpenseController extends Controller {
    private $db;
    private $expense;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->expense = new Expense();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            if ($role === 'user') {
                $expenses = $this->expense->getByUserId($user_id);
            } else {
                $expenses = $this->expense->getAll();
            }
            
            $stats = $this->expense->getStats($role === 'user' ? $user_id : null);
            
            $data = [
                'expenses' => $expenses,
                'stats' => $stats,
                'user_role' => $role
            ];
            
            $this->view('expenses/index', $data);
        } catch (Exception $e) {
            error_log('Expense index error: ' . $e->getMessage());
            $data = [
                'expenses' => [],
                'stats' => ['total' => 0, 'pending' => 0, 'approved_amount' => 0, 'rejected' => 0],
                'user_role' => $_SESSION['role'],
                'error' => 'Unable to load expense data. Please try again.'
            ];
            $this->view('expenses/index', $data);
        }
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                die('CSRF validation failed');
            }
            
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
                'category' => Security::sanitizeString($_POST['category']),
                'amount' => Security::validateInt($_POST['amount'], 1),
                'description' => Security::sanitizeString($_POST['description'], 500),
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
        
        $this->view('expenses/create');
    }
    
    public function approve($id) {
        AuthMiddleware::requireAuth();
        AuthMiddleware::requireRole(['admin', 'owner']);
        
        // Validate CSRF token for GET requests with token parameter
        if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        if ($_SESSION['role'] !== 'user') {
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
        AuthMiddleware::requireAuth();
        AuthMiddleware::requireRole(['admin', 'owner']);
        
        // Validate CSRF token for GET requests with token parameter
        if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF validation failed');
        }
        
        if ($_SESSION['role'] !== 'user') {
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
        AuthMiddleware::requireAuth();
        
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
            'category' => Security::sanitizeString($input['category'] ?? ''),
            'amount' => Security::validateInt($input['amount'] ?? 0, 1),
            'description' => Security::sanitizeString($input['description'] ?? '', 500),
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