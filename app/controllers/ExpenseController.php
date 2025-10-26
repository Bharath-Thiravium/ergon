<?php
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class ExpenseController extends Controller {
    private $expense;
    
    public function __construct() {
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
            
            $data = [
                'expenses' => $expenses ?? [],
                'user_role' => $role,
                'active_page' => 'expenses'
            ];
            
            $this->view('expenses/index', $data);
        } catch (Exception $e) {
            error_log('Expense index error: ' . $e->getMessage());
            $data = [
                'expenses' => [],
                'user_role' => $_SESSION['role'],
                'error' => 'Unable to load expense data.',
                'active_page' => 'expenses'
            ];
            $this->view('expenses/index', $data);
        }
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            
            if (empty($_POST['category']) || empty($_POST['amount']) || empty($_POST['description'])) {
                $data = ['error' => 'All fields are required', 'active_page' => 'expenses'];
                $this->view('expenses/create', $data);
                return;
            }
            
            $amount = Security::validateInt($_POST['amount'], 1);
            if (!$amount) {
                $data = ['error' => 'Invalid amount', 'active_page' => 'expenses'];
                $this->view('expenses/create', $data);
                return;
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
                'user_id' => $userId,
                'category' => Security::sanitizeString($_POST['category']),
                'amount' => $amount,
                'description' => Security::sanitizeString($_POST['description'], 500),
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'attachment' => $attachment
            ];
            
            if ($this->expense->create($data)) {
                header('Location: /ergon/public/expenses?success=1');
                exit;
            } else {
                $data = ['error' => 'Failed to create expense request', 'active_page' => 'expenses'];
                $this->view('expenses/create', $data);
                return;
            }
        }
        
        $data = ['active_page' => 'expenses'];
        $this->view('expenses/create', $data);
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
            header('Location: /ergon/public/expenses?error=invalid_id');
            exit;
        }
        
        try {
            $this->expense->updateStatus($id, 'approved', $_SESSION['user_id']);
            header('Location: /ergon/public/expenses?success=approved');
        } catch (Exception $e) {
            error_log('Expense approval error: ' . $e->getMessage());
            header('Location: /ergon/public/expenses?error=approval_failed');
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
            header('Location: /ergon/public/expenses?error=invalid_id');
            exit;
        }
        
        try {
            $this->expense->updateStatus($id, 'rejected', $_SESSION['user_id']);
            header('Location: /ergon/public/expenses?success=rejected');
        } catch (Exception $e) {
            error_log('Expense rejection error: ' . $e->getMessage());
            header('Location: /ergon/public/expenses?error=rejection_failed');
        }
        exit;
    }
}
?>
