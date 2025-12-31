<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';
require_once __DIR__ . '/../config/environment.php';

// Enhanced error logging for advance operations
function logAdvanceError($message, $context = []) {
    $logFile = __DIR__ . '/../../storage/advance_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

class AdvanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'] ?? 'user';
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure table exists with all required columns
            DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS advances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                project_id INT NULL,
                type VARCHAR(50) DEFAULT 'General Advance',
                amount DECIMAL(10,2) NOT NULL,
                reason TEXT NOT NULL,
                requested_date DATE NULL,
                repayment_date DATE NULL,
                repayment_months INT DEFAULT 1,
                status VARCHAR(20) DEFAULT 'pending',
                approved_by INT NULL,
                approved_at DATETIME NULL,
                payment_proof VARCHAR(255) NULL,
                paid_by INT NULL,
                paid_at DATETIME NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                rejected_by INT NULL,
                rejected_at TIMESTAMP NULL
            )", "Create table");
            
            // Add missing columns if they don't exist
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN project_id INT NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN repayment_date DATE NULL", "Alter table"); } catch (Exception $e) {}
            // Ensure payment columns exist
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN payment_proof VARCHAR(255) NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN paid_by INT NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN paid_at DATETIME NULL", "Alter table"); } catch (Exception $e) {}
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN approved_amount DECIMAL(10,2) NULL", "Alter table"); } catch (Exception $e) {}
            
            if ($role === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name FROM advances a JOIN users u ON a.user_id = u.id LEFT JOIN projects p ON a.project_id = p.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $db->query("SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name FROM advances a JOIN users u ON a.user_id = u.id LEFT JOIN projects p ON a.project_id = p.id ORDER BY a.created_at DESC");
            }
            $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('advances/index', ['advances' => $advances, 'user_role' => $role, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance index error: ' . $e->getMessage());
            $this->view('advances/index', ['advances' => [], 'user_role' => $_SESSION['role'] ?? 'user', 'error' => 'Unable to load advances', 'active_page' => 'advances']);
        }
    }
    
    public function create() {
        $this->requireAuth();
        $this->view('advances/create');
    }
    
    public function store() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $repaymentDate = !empty($_POST['repayment_date']) ? $_POST['repayment_date'] : null;
                $stmt = $db->prepare("INSERT INTO advances (user_id, project_id, type, amount, reason, requested_date, repayment_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['project_id'] ?: null,
                    trim($_POST['type'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    trim($_POST['reason'] ?? ''),
                    date('Y-m-d'),
                    $repaymentDate
                ]);
                
                if ($result) {
                    $advanceId = $db->lastInsertId();
                    try {
                        require_once __DIR__ . '/../helpers/NotificationHelper.php';
                        NotificationHelper::notifyAdvanceRequest($advanceId, $_SESSION['user_id']);
                    } catch (Exception $notifError) {
                        error_log('Notification error (non-critical): ' . $notifError->getMessage());
                    }
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create advance']);
                }
                exit;
            } catch (Exception $e) {
                error_log('Advance store error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    public function edit($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Check if user owns this advance or is admin/owner
                $stmt = $db->prepare("SELECT user_id, status FROM advances WHERE id = ?");
                $stmt->execute([$id]);
                $advance = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$advance) {
                    header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found');
                    exit;
                }
                
                // Only allow editing if user owns it or is admin/owner, and status is pending
                $canEdit = (($advance['user_id'] == $_SESSION['user_id']) || 
                           in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) && 
                           $advance['status'] === 'pending';
                
                if (!$canEdit) {
                    header('Location: ' . Environment::getBaseUrl() . '/advances?error=Cannot edit this advance');
                    exit;
                }
                
                header('Content-Type: application/json');
                $repaymentDate = !empty($_POST['repayment_date']) ? $_POST['repayment_date'] : null;
                $stmt = $db->prepare("UPDATE advances SET project_id = ?, type = ?, amount = ?, reason = ?, repayment_date = ? WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['project_id'] ?: null,
                    trim($_POST['type'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    trim($_POST['reason'] ?? ''),
                    $repaymentDate,
                    $id
                ]);
                
                echo json_encode(['success' => $result]);
                exit;
            } catch (Exception $e) {
                error_log('Advance edit error: ' . $e->getMessage());
                header('Location: ' . Environment::getBaseUrl() . '/advances/edit/' . $id . '?error=Update failed');
                exit;
            }
        }
        
        // GET request - show edit form
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if ($_SESSION['role'] === 'user') {
                $stmt = $db->prepare("SELECT * FROM advances WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT * FROM advances WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found');
                exit;
            }
            
            if ($advance['status'] !== 'pending') {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Cannot edit processed advance');
                exit;
            }
            
            $this->view('advances/edit', ['advance' => $advance, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance edit load error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Failed to load advance');
            exit;
        }
    }
    
    public function approve($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check authentication first
        if (!isset($_SESSION['user_id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/login');
                exit;
            }
        }
        
        // Check authorization - only admin/owner can approve
        $currentUserRole = $_SESSION['role'] ?? 'user';
        if (!in_array($currentUserRole, ['admin', 'owner'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
                exit;
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Unauthorized access');
                exit;
            }
        }
        
        if (!$id) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid advance ID']);
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Invalid advance ID');
            }
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get advance details first
            $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'pending'");
            $stmt->execute([$id]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || $this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Advance not found or already processed']);
                } else {
                    header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found or already processed');
                }
                exit;
            }
            
            // Handle POST request for approval
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                
                $approvedAmount = isset($_POST['approved_amount']) && $_POST['approved_amount'] > 0 ? floatval($_POST['approved_amount']) : floatval($advance['amount']);
                $approvalRemarks = trim($_POST['approval_remarks'] ?? '');
                
                // Add approval_remarks column if it doesn't exist
                try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN approval_remarks TEXT NULL", "Alter table"); } catch (Exception $e) {}
                
                // Update advance with approval details
                $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = ?, approval_remarks = ? WHERE id = ?");
                $result = $stmt->execute([$_SESSION['user_id'], $approvedAmount, $approvalRemarks, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Create notification for user
                    try {
                        require_once __DIR__ . '/../helpers/NotificationHelper.php';
                        NotificationHelper::notifyAdvanceStatusChange($id, 'approved', $_SESSION['user_id']);
                    } catch (Exception $notifError) {
                        error_log('Advance approval notification error: ' . $notifError->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Advance approved successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to approve advance']);
                }
                exit;
            }
            
            // GET request - check if it's an AJAX request or direct browser access
            if ($this->isAjaxRequest()) {
                // AJAX request - return JSON for modal
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'advance' => $advance
                ]);
                exit;
            } else {
                // Direct browser access - show approval page
                $this->view('advances/approve', ['advance' => $advance, 'active_page' => 'advances']);
                exit;
            }
            
        } catch (Exception $e) {
            logAdvanceError('Advance approval error: ' . $e->getMessage(), [
                'advance_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'is_ajax' => $this->isAjaxRequest(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Approval failed: ' . $e->getMessage()]);
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Approval failed');
            }
        }
        exit;
    }

    public function markPaid($id = null) {
        $this->requireAuth();
        if (!$id) {
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Invalid advance ID');
            exit;
        }

        // Only admin/owner can mark paid
        if (!in_array($_SESSION['role'] ?? 'user', ['admin','owner'])) {
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Unauthorized');
            exit;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            $db = Database::connect();

            $stmt = $db->prepare("SELECT * FROM advances WHERE id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$advance) {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found or not approved');
                exit;
            }

            $proof = null;
            $paymentRemarks = trim($_POST['payment_remarks'] ?? '');
            
            // Validate that either proof or remarks is provided
            $hasFile = isset($_FILES['proof']) && $_FILES['proof']['error'] === 0;
            if (!$hasFile && empty($paymentRemarks)) {
                header('Location: ' . Environment::getBaseUrl() . '/advances/view/' . $id . '?error=Either payment proof or payment details must be provided');
                exit;
            }

            // Handle file upload if provided
            if ($hasFile) {
                $file = $_FILES['proof'];
                $allowedMime = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                $maxSize = 5 * 1024 * 1024;
                
                if ($file['size'] > $maxSize) {
                    header('Location: ' . Environment::getBaseUrl() . '/advances/view/' . $id . '?error=File exceeds 5MB');
                    exit;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mime, $allowedMime)) {
                    header('Location: ' . Environment::getBaseUrl() . '/advances/view/' . $id . '?error=Invalid file type');
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
                    header('Location: ' . Environment::getBaseUrl() . '/advances/view/' . $id . '?error=Failed to save proof file');
                    exit;
                }
            }

            // Add payment_remarks column if it doesn't exist
            try { DatabaseHelper::safeExec($db, "ALTER TABLE advances ADD COLUMN payment_remarks TEXT NULL", "Alter table"); } catch (Exception $e) {}

            // Update advance with payment details
            $stmt = $db->prepare("UPDATE advances SET status = 'paid', payment_proof = ?, payment_remarks = ?, paid_by = ?, paid_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$proof, $paymentRemarks, $_SESSION['user_id'], $id]);

            if ($result) {
                // Record ledger credit for the advance amount
                // Use approved_amount if present else original amount
                $ledgerAmount = floatval($advance['amount']);
                try {
                    $stmt2 = $db->prepare("SELECT approved_amount FROM advances WHERE id = ? LIMIT 1");
                    $stmt2->execute([$id]);
                    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($row2 && $row2['approved_amount']) $ledgerAmount = floatval($row2['approved_amount']);
                } catch (Exception $e) {}
                LedgerHelper::recordEntry($advance['user_id'], 'advance', 'advance', $id, $ledgerAmount, 'credit');
                header('Location: ' . Environment::getBaseUrl() . '/advances?success=Advance marked as paid');
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Failed to mark paid');
            }
        } catch (Exception $e) {
            error_log('Advance markPaid error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Failed to mark paid');
        }
        exit;
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
        
        if (!$id) {
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Invalid advance ID');
            exit;
        }
        
        // Check authorization - only admin/owner can reject
        $currentUserRole = $_SESSION['role'] ?? 'user';
        if (!in_array($currentUserRole, ['admin', 'owner'])) {
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Unauthorized access');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $reason = $_POST['rejection_reason'] ?? 'Rejected by administrator';
            
            // Get advance details first
            $stmt = $db->prepare("SELECT user_id FROM advances WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found or already processed');
                exit;
            }
            
            $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ? AND status = 'pending'");
            $result = $stmt->execute([$reason, $_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Create notification for user
                try {
                    require_once __DIR__ . '/../helpers/NotificationHelper.php';
                    NotificationHelper::notifyAdvanceStatusChange($id, 'rejected', $_SESSION['user_id']);
                } catch (Exception $notifError) {
                    error_log('Advance rejection notification error: ' . $notifError->getMessage());
                }
                
                header('Location: ' . Environment::getBaseUrl() . '/advances?success=Advance rejected successfully');
            } else {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=Advance not found or already processed');
            }
        } catch (Exception $e) {
            error_log('Advance reject error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=Failed to reject advance');
        }
        exit;
    }
    
    public function viewAdvance($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if ($_SESSION['role'] === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name FROM advances a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN projects p ON a.project_id = p.id WHERE a.id = ? AND a.user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name FROM advances a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN projects p ON a.project_id = p.id WHERE a.id = ?");
                $stmt->execute([$id]);
            }
            
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: ' . Environment::getBaseUrl() . '/advances?error=not_found');
                exit;
            }
            
            $this->view('advances/view', ['advance' => $advance, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance view error: ' . $e->getMessage());
            header('Location: ' . Environment::getBaseUrl() . '/advances?error=1');
            exit;
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("DELETE FROM advances WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        exit;
    }
}
?>
