<?php
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';
require_once __DIR__ . '/../config/environment.php';

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
                payment_proof VARCHAR(255) NULL,
                paid_by INT NULL,
                paid_at DATETIME NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                approved_by INT NULL,
                approved_at TIMESTAMP NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            DatabaseHelper::safeExec($db, $sql, "Execute SQL");
            
            // Check if attachment column exists in existing table
            $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'attachment'");
            if ($stmt->rowCount() == 0) {
                DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN attachment VARCHAR(255) NULL", "Alter table");
                error_log('Added attachment column to existing expenses table');
            }
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN payment_proof VARCHAR(255) NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN paid_by INT NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN paid_at DATETIME NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN approved_amount DECIMAL(10,2) NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN approval_remarks TEXT NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses ADD COLUMN payment_remarks TEXT NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE expenses MODIFY COLUMN status ENUM('pending','approved','rejected','paid') DEFAULT 'pending'", "Alter table"); } catch (Exception $e) {}

            // Create approved_expenses table to store approved/processed expense records separately
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS approved_expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                expense_id INT NOT NULL,
                user_id INT NOT NULL,
                category VARCHAR(100) NOT NULL,
                claimed_amount DECIMAL(10,2) NOT NULL,
                approved_amount DECIMAL(10,2) NULL,
                description TEXT,
                approved_by INT NULL,
                approved_at DATETIME NULL,
                payment_proof VARCHAR(255) NULL,
                paid_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )", "Create table");
            // Ensure columns exist for backward compatibility
            try { DatabaseHelper::safeExec($db, "ALTER TABLE approved_expenses ADD COLUMN claimed_amount DECIMAL(10,2) NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE approved_expenses ADD COLUMN approved_amount DECIMAL(10,2) NULL", "Alter table"); } catch (Exception $e) {}
            
        } catch (Exception $e) {
            error_log('Error ensuring expense tables: ' . $e->getMessage());
        }
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            if ($role === 'user') {
                $expenses = $this->getExpensesForUser($user_id);
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
    
    private function getExpensesForUser($userId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.user_id = ? ORDER BY e.created_at DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting expenses for user: ' . $e->getMessage());
            return [];
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
                'project_id' => $_POST['project_id'] ?: null,
                'category' => Security::sanitizeString($_POST['category']),
                'amount' => $amount,
                'description' => Security::sanitizeString($_POST['description'], 500),
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'attachment' => $attachment
            ];
            
            if ($this->expense->create($data)) {
                // Get the expense ID and create notification
                try {
                    require_once __DIR__ . '/../helpers/NotificationHelper.php';
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::connect();
                    $expenseId = $db->lastInsertId();
                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        NotificationHelper::notifyExpenseClaim($userId, $user['name'], $amount, $expenseId);
                    }
                } catch (Exception $notifError) {
                    error_log('Notification error (non-critical): ' . $notifError->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Expense claim submitted successfully', 'redirect' => Environment::getBaseUrl() . '/expenses']);
            } else {
                // Fallback: try direct database insertion
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::connect();
                    
                    $stmt = $db->prepare("INSERT INTO expenses (user_id, project_id, category, amount, description, expense_date, attachment, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $result = $stmt->execute([
                        $data['user_id'],
                        $data['project_id'],
                        $data['category'],
                        $data['amount'],
                        $data['description'],
                        $data['expense_date'],
                        $data['attachment']
                    ]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Expense claim submitted successfully', 'redirect' => Environment::getBaseUrl() . '/expenses']);
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
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=invalid_id');
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
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=not_found');
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
                
                header('Content-Type: application/json');
                $stmt = $db->prepare("UPDATE expenses SET project_id = ?, category = ?, amount = ?, description = ?, expense_date = ?, attachment = ? WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['project_id'] ?: null,
                    $_POST['category'] ?? $expense['category'],
                    floatval($_POST['amount'] ?? $expense['amount']),
                    $_POST['description'] ?? $expense['description'],
                    $_POST['expense_date'] ?? $expense['expense_date'],
                    $attachment,
                    $id
                ]);
                
                echo json_encode(['success' => $result]);
                exit;
                
                if ($result) {
                    header('Location: ' . Environment::getBaseUrl() . '/expenses?success=updated');
                } else {
                    header('Location: ' . Environment::getBaseUrl() . '/expenses/edit/' . $id . '?error=1');
                }
                exit;
            }
            
            $this->view('expenses/edit', ['expense' => $expense, 'active_page' => 'expenses']);
        } catch (Exception $e) {
            error_log('Expense edit error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=1');
            exit;
        }
    }
    
    public function viewExpense($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT e.*, u.name as user_name, p.name as project_name FROM expenses e LEFT JOIN users u ON e.user_id = u.id LEFT JOIN projects p ON e.project_id = p.id WHERE e.id = ?");
            $stmt->execute([$id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$expense) {
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=not_found');
                exit;
            }
            
            $stmt = $db->prepare("SELECT * FROM approved_expenses WHERE expense_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$id]);
            $approved = $stmt->fetch(PDO::FETCH_ASSOC);

            $data = [
                'expense' => $expense,
                'approved' => $approved,
                'active_page' => 'expenses'
            ];
            
            $this->view('expenses/view', $data);
        } catch (Exception $e) {
            error_log('Expense view error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=view_failed');
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
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'error' => 'Invalid expense ID']);
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Invalid expense ID');
            }
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
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    echo json_encode(['success' => false, 'error' => 'Expense not found or already processed']);
                } else {
                    header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Expense not found or already processed');
                }
                exit;
            }
            
            // Handle POST request for approval
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                
                $approvedAmount = isset($_POST['approved_amount']) && $_POST['approved_amount'] > 0 ? floatval($_POST['approved_amount']) : floatval($expense['amount']);
                $approvalRemarks = trim($_POST['approval_remarks'] ?? '');
                
                // Update expense with approval details
                $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = ?, approval_remarks = ? WHERE id = ?");
                $result = $stmt->execute([$_SESSION['user_id'], $approvedAmount, $approvalRemarks, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Create notification for user
                    try {
                        require_once __DIR__ . '/../helpers/NotificationHelper.php';
                        NotificationHelper::notifyExpenseStatusChange($id, 'approved', $_SESSION['user_id']);
                    } catch (Exception $notifError) {
                        error_log('Expense approval notification error: ' . $notifError->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Expense approved successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to approve expense']);
                }
                exit;
            }
            
            // GET request - return expense data for modal
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'expense' => $expense
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log('Expense approval error: ' . $e->getMessage());
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'error' => 'Approval failed']);
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Approval failed');
            }
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
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Invalid expense ID');
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
                    // Create notification for user
                    try {
                        require_once __DIR__ . '/../helpers/NotificationHelper.php';
                        NotificationHelper::notifyExpenseStatusChange($id, 'rejected', $_SESSION['user_id']);
                    } catch (Exception $notifError) {
                        error_log('Expense rejection notification error: ' . $notifError->getMessage());
                    }
                    
                    $db->commit();
                    header('Location: ' . Environment::getBaseUrl() . '/expenses?success=Expense rejected successfully');
                } else {
                    $db->rollback();
                    header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Expense not found');
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                error_log('Expense rejection error: ' . $e->getMessage());
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Rejection failed: ' . $e->getMessage());
            }
        } else {
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Rejection reason is required');
        }
        exit;
    }

    public function markPaid($id = null) {
        AuthMiddleware::requireAuth();
        if (!$id) {
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Invalid expense ID');
            exit;
        }

        // Only admin/owner can mark paid
        if (!in_array($_SESSION['role'] ?? 'user', ['admin','owner'])) {
            header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Unauthorized');
            exit;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            $db = Database::connect();

            $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$expense) {
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Expense not found or not approved');
                exit;
            }

            $proof = null;
            $paymentRemarks = trim($_POST['payment_remarks'] ?? '');
            
            // Validate that either proof or remarks is provided
            $hasFile = isset($_FILES['proof']) && $_FILES['proof']['error'] === 0;
            if (!$hasFile && empty($paymentRemarks)) {
                header('Location: ' . Environment::getBaseUrl() . '/expenses/view/' . $id . '?error=Either payment proof or payment details must be provided');
                exit;
            }

            // Handle file upload if provided
            if ($hasFile) {
                $file = $_FILES['proof'];
                $allowedMime = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                $maxSize = 5 * 1024 * 1024;
                
                if ($file['size'] > $maxSize) {
                    error_log("markPaid expense $id: File too large (" . $file['size'] . " bytes)");
                    header('Location: ' . Environment::getBaseUrl() . '/expenses/view/' . $id . '?error=File exceeds 5MB');
                    exit;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mime, $allowedMime)) {
                    error_log("markPaid expense $id: Invalid mime type: $mime");
                    header('Location: ' . Environment::getBaseUrl() . '/expenses/view/' . $id . '?error=Invalid file type');
                    exit;
                }

                $uploadDir = __DIR__ . '/../../storage/proofs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $proof = $filename;
                } else {
                    header('Location: ' . Environment::getBaseUrl() . '/expenses/view/' . $id . '?error=Failed to save proof file');
                    exit;
                }
            }

            // Update expense with payment details
            $stmt = $db->prepare("UPDATE expenses SET status = 'paid', payment_proof = ?, payment_remarks = ?, paid_by = ?, paid_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$proof, $paymentRemarks, $_SESSION['user_id'], $id]);

            if ($result) {
                // Update approved_expenses record with proof and paid_at if exists
                try {
                    $upd = $db->prepare("UPDATE approved_expenses SET payment_proof = ?, paid_at = NOW() WHERE expense_id = ?");
                    $updResult = $upd->execute([$proof, $id]);
                    error_log("markPaid: approved_expenses update result: " . ($updResult ? 'success' : 'failed'));
                } catch (Exception $ue) {
                    error_log('Failed to update approved_expenses with proof: ' . $ue->getMessage());
                }
                // Determine approved amount (from approved_expenses) and record ledger debit
                try {
                    $stmt2 = $db->prepare("SELECT approved_amount FROM approved_expenses WHERE expense_id = ? ORDER BY id DESC LIMIT 1");
                    $stmt2->execute([$id]);
                    $approvedRow = $stmt2->fetch(PDO::FETCH_ASSOC);
                    $ledgerAmount = $approvedRow && $approvedRow['approved_amount'] ? floatval($approvedRow['approved_amount']) : floatval($expense['amount']);
                } catch (Exception $le) {
                    $ledgerAmount = floatval($expense['amount']);
                }
                LedgerHelper::recordEntry($expense['user_id'], 'expense', 'expense', $id, $ledgerAmount, 'debit');
                header('Location: ' . Environment::getBaseUrl() . '/expenses?success=Expense marked as paid');
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/expenses?error=Failed to mark paid');
            }
        } catch (Exception $e) {
            error_log('Expense markPaid error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/expenses/view/' . $id . '?error=' . urlencode($e->getMessage()));
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
