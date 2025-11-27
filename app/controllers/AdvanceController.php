<?php
require_once __DIR__ . '/../core/Controller.php';

class AdvanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'] ?? 'user';
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure table exists with repayment_date column
            $db->exec("CREATE TABLE IF NOT EXISTS advances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) DEFAULT 'General Advance',
                amount DECIMAL(10,2) NOT NULL,
                reason TEXT NOT NULL,
                requested_date DATE NULL,
                repayment_date DATE NULL,
                repayment_months INT DEFAULT 1,
                status VARCHAR(20) DEFAULT 'pending',
                approved_by INT NULL,
                approved_at DATETIME NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                rejected_by INT NULL,
                rejected_at TIMESTAMP NULL
            )");
            
            // Add repayment_date column if it doesn't exist
            try {
                $db->exec("ALTER TABLE advances ADD COLUMN repayment_date DATE NULL");
            } catch (Exception $e) {
                // Column already exists, ignore error
            }
            
            if ($role === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $db->query("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
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
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO advances (user_id, type, amount, reason, requested_date, repayment_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    trim($_POST['type'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    trim($_POST['reason'] ?? ''),
                    date('Y-m-d'),
                    $_POST['repayment_date'] ?? null
                ]);
                
                if ($result) {
                    $advanceId = $db->lastInsertId();
                    $amount = floatval($_POST['amount'] ?? 0);
                    
                    // Create notification with advance ID
                    try {
                        require_once __DIR__ . '/../helpers/NotificationHelper.php';
                        $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            NotificationHelper::notifyAdvanceRequest($_SESSION['user_id'], $user['name'], $amount, $advanceId);
                        }
                    } catch (Exception $notifError) {
                        error_log('Notification error (non-critical): ' . $notifError->getMessage());
                    }
                    
                    header('Location: /ergon/advances?success=1');
                } else {
                    header('Location: /ergon/advances/create?error=1');
                }
                exit;
            } catch (Exception $e) {
                error_log('Advance store error: ' . $e->getMessage());
                header('Location: /ergon/advances/create?error=1');
                exit;
            }
        }
        
        header('Location: /ergon/advances/create');
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
                    header('Location: /ergon/advances?error=Advance not found');
                    exit;
                }
                
                // Only allow editing if user owns it or is admin/owner, and status is pending
                $canEdit = (($advance['user_id'] == $_SESSION['user_id']) || 
                           in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) && 
                           $advance['status'] === 'pending';
                
                if (!$canEdit) {
                    header('Location: /ergon/advances?error=Cannot edit this advance');
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE advances SET type = ?, amount = ?, reason = ?, repayment_date = ? WHERE id = ?");
                $result = $stmt->execute([
                    trim($_POST['type'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    trim($_POST['reason'] ?? ''),
                    $_POST['repayment_date'] ?? null,
                    $id
                ]);
                
                if ($result) {
                    header('Location: /ergon/advances?success=Advance updated successfully');
                } else {
                    header('Location: /ergon/advances/edit/' . $id . '?error=Update failed');
                }
                exit;
            } catch (Exception $e) {
                error_log('Advance edit error: ' . $e->getMessage());
                header('Location: /ergon/advances/edit/' . $id . '?error=Update failed');
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
                header('Location: /ergon/advances?error=Advance not found');
                exit;
            }
            
            if ($advance['status'] !== 'pending') {
                header('Location: /ergon/advances?error=Cannot edit processed advance');
                exit;
            }
            
            $this->view('advances/edit', ['advance' => $advance, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance edit load error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=Failed to load advance');
            exit;
        }
    }
    
    public function approve($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
        
        if (!$id) {
            header('Location: /ergon/advances?error=Invalid advance ID');
            exit;
        }
        
        // Check authorization - only admin/owner can approve
        $currentUserRole = $_SESSION['role'] ?? 'user';
        if (!in_array($currentUserRole, ['admin', 'owner'])) {
            header('Location: /ergon/advances?error=Unauthorized access');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                header('Location: /ergon/advances?success=Advance approved successfully');
            } else {
                header('Location: /ergon/advances?error=Advance not found or already processed');
            }
        } catch (Exception $e) {
            error_log('Advance approve error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=Failed to approve advance');
        }
        exit;
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
        
        if (!$id) {
            header('Location: /ergon/advances?error=Invalid advance ID');
            exit;
        }
        
        // Check authorization - only admin/owner can reject
        $currentUserRole = $_SESSION['role'] ?? 'user';
        if (!in_array($currentUserRole, ['admin', 'owner'])) {
            header('Location: /ergon/advances?error=Unauthorized access');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $reason = $_POST['rejection_reason'] ?? 'Rejected by administrator';
            
            $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ? AND status = 'pending'");
            $result = $stmt->execute([$reason, $_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                header('Location: /ergon/advances?success=Advance rejected successfully');
            } else {
                header('Location: /ergon/advances?error=Advance not found or already processed');
            }
        } catch (Exception $e) {
            error_log('Advance reject error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=Failed to reject advance');
        }
        exit;
    }
    
    public function viewAdvance($id) {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if ($_SESSION['role'] === 'user') {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
                $stmt->execute([$id]);
            }
            
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advance) {
                header('Location: /ergon/advances?error=not_found');
                exit;
            }
            
            $this->view('advances/view', ['advance' => $advance, 'active_page' => 'advances']);
        } catch (Exception $e) {
            error_log('Advance view error: ' . $e->getMessage());
            header('Location: /ergon/advances?error=1');
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