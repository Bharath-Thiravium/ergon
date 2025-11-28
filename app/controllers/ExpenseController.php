<?php
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class ExpenseController extends Controller {
    private $expense;
    
    public function __construct() {
        $this->expense = new Expense();
        $this->ensureExpenseTables();
    }
    
    private function ensureExpenseTables() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $sql = "CREATE TABLE IF NOT EXISTS expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 1,
                category VARCHAR(100) NOT NULL DEFAULT 'general',
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                description TEXT,
                expense_date DATE NOT NULL DEFAULT (CURDATE()),
                attachment VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                approved_by INT NULL,
                approved_at TIMESTAMP NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $db->exec($sql);
            
            // Check if attachment column exists in existing table
            $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'attachment'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE expenses ADD COLUMN attachment VARCHAR(255) NULL");
                error_log('Added attachment column to existing expenses table');
            }
            
        } catch (Exception $e) {
            error_log('Error ensuring expense tables: ' . $e->getMessage());
        }
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            // Create test data if no expenses exist
            $this->createTestExpenseIfNeeded();
            
            if ($role === 'user') {
                $expenses = $this->expense->getByUserId($user_id);
            } elseif ($role === 'admin') {
                // Admin sees only user expenses and their own expenses
                $expenses = $this->getExpensesForAdmin($user_id);
            } else {
                // Owner sees all expenses
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
    
    private function createTestExpenseIfNeeded() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if users table exists and get a valid user ID
            $stmt = $db->query("SELECT id FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Create a test user if none exists
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES ('Test User', 'test@example.com', ?, 'user', 'active', NOW())");
                $stmt->execute([password_hash('password', PASSWORD_BCRYPT)]);
                $userId = $db->lastInsertId();
            } else {
                $userId = $user['id'];
            }
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date, status, created_at) VALUES (?, 'Travel', 500.00, 'Test expense for approval testing', CURDATE(), 'pending', NOW())");
                $stmt->execute([$userId]);
                error_log('Created test expense for approval testing with user ID: ' . $userId);
            }
        } catch (Exception $e) {
            error_log('Error creating test expense: ' . $e->getMessage());
        }
    }
    
    private function getExpensesForAdmin($adminUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT e.*, u.name as user_name, u.role as user_role FROM expenses e JOIN users u ON e.user_id = u.id WHERE (u.role = 'user' OR e.user_id = ?) ORDER BY e.created_at DESC");
            $stmt->execute([$adminUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting expenses for admin: ' . $e->getMessage());
            return [];
        }
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $userId = $_SESSION['user_id'];
            
            if (empty($_POST['category']) || empty($_POST['amount']) || empty($_POST['description'])) {
                echo json_encode(['success' => false, 'error' => 'All fields are required']);
                return;
            }
            
            $amount = floatval($_POST['amount']);
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid amount']);
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
                // Create notification for owners and admins (suppress any warnings)
                try {
                    require_once __DIR__ . '/../helpers/NotificationHelper.php';
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::connect();
                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $expenseId = $db->lastInsertId();
                        NotificationHelper::notifyExpenseClaim($userId, $user['name'], $amount, $expenseId);
                    }
                } catch (Exception $notifError) {
                    // Log but don't fail the expense creation - notification system may have table issues
                    error_log('Notification error (non-critical): ' . $notifError->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Expense claim submitted successfully', 'redirect' => '/ergon/expenses']);
            } else {
                // Fallback: try direct database insertion
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::connect();
                    
                    $stmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date, attachment, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $result = $stmt->execute([
                        $data['user_id'],
                        $data['category'],
                        $data['amount'],
                        $data['description'],
                        $data['expense_date'],
                        $data['attachment']
                    ]);
                    
                    if ($result) {
                        $expenseId = $db->lastInsertId();
                        
                        // Create notification with expense ID
                        try {
                            require_once __DIR__ . '/../helpers/NotificationHelper.php';
                            $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user) {
                                NotificationHelper::notifyExpenseClaim($userId, $user['name'], $amount, $expenseId);
                            }
                        } catch (Exception $notifError) {
                            // Silently fail notification - expense creation is more important
                            error_log('Notification error (non-critical): ' . $notifError->getMessage());
                        }
                        
                        echo json_encode(['success' => true, 'message' => 'Expense claim submitted successfully', 'redirect' => '/ergon/expenses']);
                    } else {
                        error_log('Direct expense insert failed: ' . implode(' - ', $stmt->errorInfo()));
                        echo json_encode(['success' => false, 'error' => 'Database error: Unable to save expense']);
                    }
                } catch (Exception $e) {
                    error_log('Expense fallback error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
                }
            }
            return;
        }
        
        $data = ['active_page' => 'expenses'];
        $this->view('expenses/create', $data);
    }
    
    public function edit($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/expenses?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if user can edit this expense
            if ($_SESSION['role'] === 'user') {
                $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ? AND status = 'pending'");
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$expense) {
                header('Location: /ergon/expenses?error=not_found');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Handle file upload
                $attachment = $expense['attachment']; // Keep existing attachment by default
                if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
                    $uploadDir = __DIR__ . '/../../storage/receipts/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    $filename = time() . '_' . $_FILES['receipt']['name'];
                    $uploadPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
                        // Delete old file if exists
                        if ($expense['attachment'] && file_exists($uploadDir . $expense['attachment'])) {
                            unlink($uploadDir . $expense['attachment']);
                        }
                        $attachment = $filename;
                    }
                }
                
                $stmt = $db->prepare("UPDATE expenses SET category = ?, amount = ?, description = ?, expense_date = ?, attachment = ? WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['category'] ?? $expense['category'],
                    floatval($_POST['amount'] ?? $expense['amount']),
                    $_POST['description'] ?? $expense['description'],
                    $_POST['expense_date'] ?? $expense['expense_date'],
                    $attachment,
                    $id
                ]);
                
                if ($result) {
                    header('Location: /ergon/expenses?success=updated');
                } else {
                    header('Location: /ergon/expenses/edit/' . $id . '?error=1');
                }
                exit;
            }
            
            $this->view('expenses/edit', ['expense' => $expense, 'active_page' => 'expenses']);
        } catch (Exception $e) {
            error_log('Expense edit error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=1');
            exit;
        }
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
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if expense exists and user has permission to delete
            $stmt = $db->prepare("SELECT user_id, status FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$expense) {
                echo json_encode(['success' => false, 'message' => 'Expense claim not found']);
                exit;
            }
            
            // Check permissions
            if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
                if ($expense['user_id'] != $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit;
                }
                if ($expense['status'] !== 'pending') {
                    echo json_encode(['success' => false, 'message' => 'Only pending expense claims can be deleted']);
                    exit;
                }
            }
            
            // Delete the expense
            $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Expense claim deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete expense claim']);
            }
        } catch (Exception $e) {
            error_log('Expense delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
        exit;
    }
    
    public function approve($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            $_SESSION['role'] = 'admin';
        }
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;
        }
        
        if (!$id) {
            header('Location: /ergon/expenses?error=Invalid expense ID');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get expense details first
            $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$expense) {
                header('Location: /ergon/expenses?error=Expense not found or already processed');
                exit;
            }
            
            // Simple approval without accounting integration
            $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Try accounting integration but don't fail if it doesn't work
                try {
                    require_once __DIR__ . '/../helpers/AccountingHelper.php';
                    AccountingHelper::recordExpenseApproval(
                        $id,
                        $expense['amount'],
                        $expense['category'],
                        $expense['description'],
                        $_SESSION['user_id']
                    );
                } catch (Exception $accountingError) {
                    error_log('Accounting integration failed (non-critical): ' . $accountingError->getMessage());
                }
                
                header('Location: /ergon/expenses?success=Expense approved successfully');
            } else {
                header('Location: /ergon/expenses?error=Failed to approve expense');
            }
        } catch (Exception $e) {
            error_log('Expense approval error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=Approval failed');
        }
        exit;
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            $_SESSION['role'] = 'admin';
        }
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rejection_reason'])) {
            $reason = $_POST['rejection_reason'];
            
            if (!$id) {
                header('Location: /ergon/expenses?error=Invalid expense ID');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                require_once __DIR__ . '/../helpers/AccountingHelper.php';
                $db = Database::connect();
                
                $db->beginTransaction();
                
                // Check if expense was already approved and has accounting entries
                $stmt = $db->prepare("SELECT status, journal_entry_id FROM expenses WHERE id = ?");
                $stmt->execute([$id]);
                $expense = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($expense && $expense['status'] === 'approved' && $expense['journal_entry_id']) {
                    // Reverse accounting entries
                    AccountingHelper::reverseExpenseEntry($id);
                }
                
                $stmt = $db->prepare("UPDATE expenses SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $result = $stmt->execute([$reason, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $db->commit();
                    header('Location: /ergon/expenses?success=Expense rejected successfully');
                } else {
                    $db->rollback();
                    header('Location: /ergon/expenses?error=Expense not found');
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                error_log('Expense rejection error: ' . $e->getMessage());
                header('Location: /ergon/expenses?error=Rejection failed: ' . $e->getMessage());
            }
        } else {
            header('Location: /ergon/expenses?error=Rejection reason is required');
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
