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
                header('Location: /ergon/expenses?success=1');
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
    
    public function viewExpense($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/expenses?error=invalid_id');
            exit;
        }
        
        try {
            $expense = $this->expense->getById($id);
            if (!$expense) {
                header('Location: /ergon/expenses?error=not_found');
                exit;
            }
            
            $data = [
                'expense' => $expense,
                'active_page' => 'expenses'
            ];
            
            $this->view('expenses/view', $data);
        } catch (Exception $e) {
            error_log('Expense view error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=view_failed');
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
            $result = $this->expense->delete($id);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            error_log('Expense delete error: ' . $e->getMessage());
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
            $id = $_POST['expense_id'] ?? $id;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/expenses?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/expenses?success=Expense approved successfully');
            } else {
                header('Location: /ergon/expenses?error=Failed to approve expense');
            }
        } catch (Exception $e) {
            error_log('Expense approval error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=approval_failed');
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
            $id = $_POST['expense_id'] ?? $id;
        }
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/expenses?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE expenses SET status = 'rejected', approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result) {
                header('Location: /ergon/expenses?success=Expense rejected successfully');
            } else {
                header('Location: /ergon/expenses?error=Failed to reject expense');
            }
        } catch (Exception $e) {
            error_log('Expense rejection error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=rejection_failed');
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
                
                $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['category'] ?? 'General',
                    floatval($_POST['amount'] ?? 0),
                    $_POST['description'] ?? '',
                    $_POST['expense_date'] ?? date('Y-m-d')
                ]);
                
                echo json_encode(['success' => $result, 'expense_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}
?>
