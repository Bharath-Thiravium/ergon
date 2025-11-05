<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class OwnerController extends Controller {
    
    public function approvals() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            // Get pending leaves with fallback
            try {
                $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC");
                $stmt->execute();
                $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Leaves query error: ' . $e->getMessage());
                $leaves = [];
            }
            
            // Get pending expenses with fallback
            try {
                $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
                $stmt->execute();
                $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Expenses query error: ' . $e->getMessage());
                $expenses = [];
            }
            
            // Get pending advances with fallback
            try {
                $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC");
                $stmt->execute();
                $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Advances query error: ' . $e->getMessage());
                $advances = [];
            }
            
            $data = [
                'leaves' => $leaves,
                'expenses' => $expenses,
                'advances' => $advances,
                'active_page' => 'approvals'
            ];
            
        } catch (Exception $e) {
            error_log('Owner approvals error: ' . $e->getMessage());
            $data = [
                'leaves' => [],
                'expenses' => [],
                'advances' => [],
                'active_page' => 'approvals',
                'error' => 'Unable to load approval data'
            ];
        }
        
        $this->view('owner/approvals', $data);
    }
    
    public function viewApproval($type, $id) {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            if ($type === 'leave') {
                $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                $viewFile = 'leaves/view';
            } else {
                $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                $viewFile = 'expenses/view';
            }
            
            if (!$item) {
                header('Location: /ergon/owner/approvals?error=Item not found');
                exit;
            }
            
            $data = [$type => $item, 'active_page' => 'approvals'];
            $this->view($viewFile, $data);
            
        } catch (Exception $e) {
            header('Location: /ergon/owner/approvals?error=Failed to load item');
            exit;
        }
    }
    
    public function deleteApproval($type, $id) {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            if ($type === 'leave') {
                $stmt = $db->prepare("DELETE FROM leaves WHERE id = ?");
            } else {
                $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
            }
            
            $result = $stmt->execute([$id]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
    
    public function approveRequest() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            $id = intval($_POST['id'] ?? 0);
            $remarks = $_POST['remarks'] ?? '';
            
            error_log("Approve request: type=$type, id=$id, user={$_SESSION['user_id']}");
            
            try {
                $db = Database::connect();
                
                // Ensure required columns exist before updating
                $this->ensureApprovalColumns($db);
                
                if ($type === 'leave') {
                    $stmt = $db->prepare("UPDATE leaves SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                } elseif ($type === 'expense') {
                    $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                } elseif ($type === 'advance') {
                    $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                } else {
                    error_log("Invalid approval type: $type");
                    echo json_encode(['success' => false, 'message' => 'Invalid type']);
                    exit;
                }
                
                $result = $stmt->execute([$_SESSION['user_id'], $id]);
                $rowCount = $stmt->rowCount();
                
                error_log("Approval result: success=$result, rows_affected=$rowCount");
                
                echo json_encode(['success' => $result && $rowCount > 0, 'rows_affected' => $rowCount]);
            } catch (Exception $e) {
                error_log("Approval error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Approval failed: ' . $e->getMessage()]);
            }
        }
        exit;
    }
    
    public function rejectRequest() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            $id = intval($_POST['id'] ?? 0);
            $remarks = $_POST['remarks'] ?? '';
            
            error_log("Reject request: type=$type, id=$id, remarks=$remarks");
            
            try {
                $db = Database::connect();
                
                // Ensure required columns exist before updating
                $this->ensureApprovalColumns($db);
                
                if ($type === 'leave') {
                    $stmt = $db->prepare("UPDATE leaves SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                } elseif ($type === 'expense') {
                    $stmt = $db->prepare("UPDATE expenses SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                } elseif ($type === 'advance') {
                    $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                } else {
                    error_log("Invalid rejection type: $type");
                    echo json_encode(['success' => false, 'message' => 'Invalid type']);
                    exit;
                }
                
                $result = $stmt->execute([$remarks, $id]);
                $rowCount = $stmt->rowCount();
                
                error_log("Rejection result: success=$result, rows_affected=$rowCount");
                
                echo json_encode(['success' => $result && $rowCount > 0, 'rows_affected' => $rowCount]);
            } catch (Exception $e) {
                error_log("Rejection error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Rejection failed: ' . $e->getMessage()]);
            }
        }
        exit;
    }
    
    public function dashboard() {
        $data = [
            'stats' => [
                'total_users' => 25,
                'active_tasks' => 18,
                'pending_leaves' => 3,
                'pending_expenses' => 5
            ],
            'pending_approvals' => [
                ['type' => 'Leave Requests', 'count' => 3],
                ['type' => 'Expense Claims', 'count' => 5],
                ['type' => 'Advance Requests', 'count' => 2]
            ],
            'recent_activities' => [
                ['action' => 'New User Registration', 'description' => 'John Doe joined the system', 'created_at' => '2024-01-15 10:30:00'],
                ['action' => 'Task Completed', 'description' => 'Project Alpha milestone reached', 'created_at' => '2024-01-15 09:15:00'],
                ['action' => 'Leave Approved', 'description' => 'Annual leave approved for Jane Smith', 'created_at' => '2024-01-14 16:45:00']
            ]
        ];
        
        include __DIR__ . '/../../views/owner/dashboard.php';
    }
    
    private function ensureApprovalColumns($db) {
        try {
            // Add missing columns to leaves table
            $db->exec("ALTER TABLE leaves ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
            $db->exec("ALTER TABLE leaves ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
            $db->exec("ALTER TABLE leaves ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL");
            
            // Add missing columns to expenses table
            $db->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
            $db->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
            $db->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL");
            
            // Add missing columns to advances table
            $db->exec("ALTER TABLE advances ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
            $db->exec("ALTER TABLE advances ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
            $db->exec("ALTER TABLE advances ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL");
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>
