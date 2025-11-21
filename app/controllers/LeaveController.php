<?php
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../core/Controller.php';

class LeaveController extends Controller {
    private $leave;
    
    public function __construct() {
        $this->leave = new Leave();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure leaves table exists
            $this->ensureLeavesTable($db);
            
            // Get filter parameters
            $filterEmployee = $_GET['employee'] ?? '';
            $filterLeaveType = $_GET['leave_type'] ?? '';
            $filterStatus = $_GET['status'] ?? '';
            
            // Build WHERE clause for filters
            $whereConditions = [];
            $params = [];
            
            if ($role === 'user') {
                $whereConditions[] = "l.user_id = ?";
                $params[] = $user_id;
            } elseif ($role === 'admin') {
                $whereConditions[] = "(u.role = 'user' OR l.user_id = ?)";
                $params[] = $user_id;
            }
            
            if ($filterEmployee) {
                $whereConditions[] = "l.user_id = ?";
                $params[] = $filterEmployee;
            }
            
            if ($filterLeaveType) {
                $whereConditions[] = "l.leave_type = ?";
                $params[] = $filterLeaveType;
            }
            
            if ($filterStatus) {
                $whereConditions[] = "l.status = ?";
                $params[] = $filterStatus;
            }
            
            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT l.*, u.name as user_name, u.role as user_role FROM leaves l JOIN users u ON l.user_id = u.id {$whereClause} ORDER BY l.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all employees for filter dropdown
            $employeesStmt = $db->query("SELECT id, name FROM users ORDER BY name");
            $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Map leave_type to type for display consistency
            foreach ($leaves as &$leave) {
                if (isset($leave['leave_type'])) {
                    $leave['type'] = $leave['leave_type'];
                }
            }
            
            $data = [
                'leaves' => $leaves ?? [],
                'employees' => $employees ?? [],
                'user_role' => $role,
                'active_page' => 'leaves',
                'filters' => [
                    'employee' => $filterEmployee,
                    'leave_type' => $filterLeaveType,
                    'status' => $filterStatus
                ]
            ];
            
            $this->view('leaves/index', $data);
        } catch (Exception $e) {
            error_log('Leave index error: ' . $e->getMessage());
            $data = [
                'leaves' => [],
                'user_role' => $_SESSION['role'],
                'error' => 'Unable to load leave data.',
                'active_page' => 'leaves'
            ];
            $this->view('leaves/index', $data);
        }
    }
    
    public function create() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $userId = $_SESSION['user_id'];
            
            // Validate required fields
            if (empty($_POST['type']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
                echo json_encode(['success' => false, 'error' => 'All fields are required']);
                return;
            }
            
            // Validate dates
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
                echo json_encode(['success' => false, 'error' => 'Start date cannot be in the past']);
                return;
            }
            
            if (strtotime($endDate) < strtotime($startDate)) {
                echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
                return;
            }
            
            $data = [
                'user_id' => $userId,
                'type' => Security::sanitizeString($_POST['type']),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => Security::sanitizeString($_POST['reason'] ?? '', 500)
            ];
            
            // Calculate leave days
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $days = $end->diff($start)->days + 1;
            
            // Try direct database insertion
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, days_requested, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $data['user_id'],
                    $data['type'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['reason'],
                    $days
                ]);
                
                if ($result) {
                    $leaveId = $db->lastInsertId();
                    
                    // Create notification for owners and admins
                    require_once __DIR__ . '/../helpers/NotificationHelper.php';
                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        NotificationHelper::notifyLeaveRequest($userId, $user['name']);
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully', 'days' => $days]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create leave request']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            return;
        }
        
        $data = ['active_page' => 'leaves'];
        $data = ['active_page' => 'leaves'];
        $this->view('leaves/create', $data);
    }
    
    public function store() {
        $this->create();
    }
    
    public function edit($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/leaves?error=invalid_id');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Use leaves table with leave_type column (based on model)
            $stmt = $db->prepare("SELECT * FROM leaves WHERE id = ?");
            $stmt->execute([$id]);
            $leave = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$leave) {
                header('Location: /ergon/leaves?error=not_found');
                exit;
            }
            
            // Check permissions
            if ($_SESSION['role'] === 'user' && $leave['user_id'] != $_SESSION['user_id']) {
                header('Location: /ergon/leaves?error=access_denied');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $type = trim($_POST['type'] ?? '');
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';
                $reason = trim($_POST['reason'] ?? '');
                
                if (empty($type) || empty($startDate) || empty($endDate) || empty($reason)) {
                    header('Location: /ergon/leaves/edit/' . $id . '?error=All fields are required');
                    exit;
                }
                
                if (strtotime($endDate) < strtotime($startDate)) {
                    header('Location: /ergon/leaves/edit/' . $id . '?error=End date must be after start date');
                    exit;
                }
                
                // Calculate days
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $days = $end->diff($start)->days + 1;
                
                // Update using leave_type column with days calculation
                $stmt = $db->prepare("UPDATE leaves SET leave_type = ?, start_date = ?, end_date = ?, reason = ?, days_requested = ? WHERE id = ?");
                $result = $stmt->execute([$type, $startDate, $endDate, $reason, $days, $id]);
                
                if ($result) {
                    header('Location: /ergon/leaves?success=Leave request updated successfully');
                } else {
                    header('Location: /ergon/leaves/edit/' . $id . '?error=Update failed');
                }
                exit;
            }
            
            $this->view('leaves/edit', ['leave' => $leave, 'active_page' => 'leaves']);
        } catch (Exception $e) {
            error_log('Leave edit error: ' . $e->getMessage());
            header('Location: /ergon/leaves?error=database_error');
            exit;
        }
    }
    
    public function viewLeave($id) {
        AuthMiddleware::requireAuth();
        
        $id = Security::validateInt($id);
        if (!$id) {
            header('Location: /ergon/leaves?error=invalid_id');
            exit;
        }
        
        try {
            $leave = $this->leave->getById($id);
            if (!$leave) {
                header('Location: /ergon/leaves?error=not_found');
                exit;
            }
            
            $data = [
                'leave' => $leave,
                'active_page' => 'leaves'
            ];
            
            $this->view('leaves/view', $data);
        } catch (Exception $e) {
            error_log('Leave view error: ' . $e->getMessage());
            header('Location: /ergon/leaves?error=view_failed');
            exit;
        }
    }
    
    public function delete($id) {
        header('Content-Type: application/json');
        AuthMiddleware::requireAuth();
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        
        try {
            $result = $this->leave->delete($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Leave deleted successfully' : 'Delete failed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
        
        if (!$id) {
            header('Location: /ergon/leaves?error=Invalid leave ID');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get leave details before approval
            $stmt = $db->prepare("SELECT user_id, start_date, end_date FROM leaves WHERE id = ? AND status = 'Pending'");
            $stmt->execute([$id]);
            $leave = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$leave) {
                header('Location: /ergon/leaves?error=Leave not found or already processed');
                exit;
            }
            
            // Approve the leave
            $stmt = $db->prepare("UPDATE leaves SET status = 'Approved' WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                header('Location: /ergon/leaves?success=Leave approved successfully');
            } else {
                header('Location: /ergon/leaves?error=Failed to approve leave');
            }
        } catch (Exception $e) {
            header('Location: /ergon/leaves?error=Database error: ' . $e->getMessage());
        }
        exit;
    }
    
    private function createLeaveAttendanceRecords($db, $userId, $startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($start <= $end) {
            $currentDate = $start->format('Y-m-d');
            
            // Check if attendance record already exists
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$userId, $currentDate]);
            
            if (!$stmt->fetch()) {
                // Create attendance record for leave with proper leave marking
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name, created_at) VALUES (?, ?, 'present', 'On Approved Leave', NOW())");
                $stmt->execute([$userId, $currentDate . ' 09:00:00']);
            } else {
                // Update existing record to mark as leave
                $stmt = $db->prepare("UPDATE attendance SET status = 'present', location_name = 'On Approved Leave' WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $currentDate]);
            }
            
            $start->add(new DateInterval('P1D'));
        }
    }
    
    public function reject($id = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            $_SESSION['role'] = 'admin';
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rejection_reason'])) {
            $reason = $_POST['rejection_reason'];
            
            if (!$id) {
                header('Location: /ergon/leaves?error=Invalid leave ID');
                exit;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                // Get leave details before rejection
                $stmt = $db->prepare("SELECT user_id, start_date, end_date FROM leaves WHERE id = ? AND status = 'Pending'");
                $stmt->execute([$id]);
                $leave = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $db->prepare("UPDATE leaves SET status = 'Rejected', rejection_reason = ? WHERE id = ? AND status = 'Pending'");
                $result = $stmt->execute([$reason, $id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Remove any leave attendance records if they exist
                    if ($leave) {
                        $this->removeLeaveAttendanceRecords($db, $leave['user_id'], $leave['start_date'], $leave['end_date']);
                    }
                    header('Location: /ergon/leaves?success=Leave rejected successfully');
                } else {
                    header('Location: /ergon/leaves?error=Leave not found or already processed');
                }
            } catch (Exception $e) {
                header('Location: /ergon/leaves?error=Database error: ' . $e->getMessage());
            }
        } else {
            header('Location: /ergon/leaves?error=Rejection reason is required');
        }
        exit;
    }
    
    private function removeLeaveAttendanceRecords($db, $userId, $startDate, $endDate) {
        try {
            $stmt = $db->prepare("DELETE FROM attendance WHERE user_id = ? AND location_name = 'On Approved Leave' AND DATE(check_in) BETWEEN ? AND ?");
            $stmt->execute([$userId, $startDate, $endDate]);
        } catch (Exception $e) {
            error_log('Remove leave attendance error: ' . $e->getMessage());
        }
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
                
                $stmt = $db->prepare("INSERT INTO leaves (user_id, type, start_date, end_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['type'] ?? 'sick',
                    $_POST['start_date'] ?? date('Y-m-d'),
                    $_POST['end_date'] ?? date('Y-m-d'),
                    $_POST['reason'] ?? ''
                ]);
                
                echo json_encode(['success' => $result, 'leave_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
    
    private function ensureLeavesTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS leaves (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                leave_type VARCHAR(50) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                days_requested INT DEFAULT 1,
                reason TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                rejection_reason TEXT NULL,
                approved_by INT NULL,
                approved_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            )");
        } catch (Exception $e) {
            error_log('ensureLeavesTable error: ' . $e->getMessage());
        }
    }
}
?>
