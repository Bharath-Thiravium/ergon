<?php
/**
 * Admin Controller - Department Admin vs System Admin
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleManager.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/Advance.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Department.php';

class AdminController extends Controller {
    
    public function dashboard() {
        AuthMiddleware::requireRole('admin');
        
        try {
            $db = Database::connect();
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            // Get role-specific statistics
            if ($isSystemAdmin) {
                $stats = $this->getSystemAdminStats($db);
                $managementOptions = $this->getSystemAdminOptions();
            } else {
                $stats = $this->getDepartmentAdminStats($db);
                $managementOptions = $this->getDepartmentAdminOptions();
            }
            
            // Get pending approvals for admin level
            $pendingApprovals = [
                'leaves' => $this->getPendingLeaves($db),
                'expenses' => $this->getPendingExpenses($db),
                'advances' => $this->getPendingAdvances($db)
            ];
            
            // Get team performance data
            $teamData = $this->getTeamData($db, $isSystemAdmin);
            
            $this->view('admin/dashboard', [
                'stats' => $stats,
                'pending_approvals' => $pendingApprovals,
                'team_data' => $teamData,
                'management_options' => $managementOptions,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'dashboard'
            ]);
            
        } catch (Exception $e) {
            error_log('Admin dashboard error: ' . $e->getMessage());
            $this->view('admin/dashboard', ['error' => 'Unable to load dashboard data']);
        }
    }
    

    
    public function manageTasks() {
        AuthMiddleware::requireRole('admin');
        
        try {
            $taskModel = new Task();
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            // System admin sees all tasks, department admin sees only their department's tasks
            if ($isSystemAdmin) {
                $tasks = $taskModel->getAll();
            } else {
                $tasks = $taskModel->getByDepartment($_SESSION['department_id'] ?? null);
            }
            
            $this->view('admin/manage_tasks', [
                'tasks' => $tasks,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'tasks'
            ]);
            
        } catch (Exception $e) {
            error_log('Manage tasks error: ' . $e->getMessage());
            $this->view('admin/manage_tasks', ['error' => 'Unable to load tasks']);
        }
    }
    
    public function approveRequest() {
        AuthMiddleware::requireRole('admin');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $_POST['type'];
            $id = $_POST['id'];
            $action = $_POST['action']; // 'approve' or 'reject'
            $comments = $_POST['comments'] ?? '';
            
            $db = Database::connect();
            $this->ensureApprovalColumns($db);
            
            // Admin provides first-level approval
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET admin_approval = ?, admin_approved_by = ?, admin_approved_at = NOW(), admin_comments = ? WHERE id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET admin_approval = ?, admin_approved_by = ?, admin_approved_at = NOW(), admin_comments = ? WHERE id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET admin_approval = ?, admin_approved_by = ?, admin_approved_at = NOW(), admin_comments = ? WHERE id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$action, $_SESSION['user_id'], $comments, $id]);
            
            if ($result) {
                // If approved by admin, it goes to owner for final approval
                // If rejected by admin, it's final
                if ($action === 'rejected') {
                    $statusStmt = $db->prepare("UPDATE {$type}s SET status = 'rejected' WHERE id = ?");
                    $statusStmt->execute([$id]);
                }
                
                $this->json(['success' => true, 'message' => ucfirst($type) . ' ' . $action . ' successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to ' . $action . ' ' . $type]);
            }
            
        } catch (Exception $e) {
            error_log('Admin approval error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function manageUsers() {
        AuthMiddleware::requireRole('admin');
        
        try {
            $userModel = new User();
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            if ($isSystemAdmin) {
                // System admin can manage all users
                $users = $userModel->getAll();
                $canCreateUsers = true;
                $canAssignRoles = true;
            } else {
                // Department admin can only view their department users
                $users = $userModel->getByDepartment($_SESSION['department_id']);
                $canCreateUsers = false; // Only basic users
                $canAssignRoles = false;
            }
            
            $this->view('admin/manage_users', [
                'users' => $users,
                'is_system_admin' => $isSystemAdmin,
                'can_create_users' => $canCreateUsers,
                'can_assign_roles' => $canAssignRoles,
                'active_page' => 'users'
            ]);
            
        } catch (Exception $e) {
            error_log('Manage users error: ' . $e->getMessage());
            $this->view('admin/manage_users', ['error' => 'Unable to load users']);
        }
    }
    
    public function createUser() {
        AuthMiddleware::requireRole('admin');
        
        $isSystemAdmin = $_SESSION['role'] === 'system_admin';
        
        if (!$isSystemAdmin) {
            // Department admin can only create basic users
            $_POST['role'] = 'user';
            $_POST['department_id'] = $_SESSION['department_id'];
        }
        
        if ($this->isPost()) {
            try {
                $userModel = new User();
                
                // Validate role assignment permissions
                if (!$isSystemAdmin && $_POST['role'] !== 'user') {
                    throw new Exception('Insufficient permissions to assign this role');
                }
                
                $result = $userModel->createEnhanced($_POST);
                
                if ($result) {
                    $this->json(['success' => true, 'message' => 'User created successfully', 'data' => $result]);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to create user']);
                }
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $departmentModel = new Department();
            $departments = $isSystemAdmin ? $departmentModel->getAll() : [$departmentModel->getById($_SESSION['department_id'])];
            
            $this->view('admin/create_user', [
                'departments' => $departments,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'users'
            ]);
        }
    }
    
    public function attendanceOverview() {
        AuthMiddleware::requireRole('admin');
        
        try {
            $attendanceModel = new Attendance();
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            if ($isSystemAdmin) {
                $attendanceData = $attendanceModel->getAllAttendance();
                $attendanceStats = $attendanceModel->getSystemStats();
            } else {
                $attendanceData = $attendanceModel->getDepartmentAttendance($_SESSION['department_id']);
                $attendanceStats = $attendanceModel->getDepartmentStats($_SESSION['department_id']);
            }
            
            $this->view('admin/attendance_overview', [
                'attendance_data' => $attendanceData,
                'attendance_stats' => $attendanceStats,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'attendance'
            ]);
            
        } catch (Exception $e) {
            error_log('Attendance overview error: ' . $e->getMessage());
            $this->view('admin/attendance_overview', ['error' => 'Unable to load attendance data']);
        }
    }
    
    public function reports() {
        AuthMiddleware::requireRole('admin');
        
        try {
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            $reportData = [
                'task_completion' => $this->getTaskCompletionReport($isSystemAdmin),
                'attendance_summary' => $this->getAttendanceReport($isSystemAdmin),
                'leave_utilization' => $this->getLeaveReport($isSystemAdmin),
                'expense_analysis' => $this->getExpenseReport($isSystemAdmin)
            ];
            
            $this->view('admin/reports', [
                'report_data' => $reportData,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'reports'
            ]);
            
        } catch (Exception $e) {
            error_log('Reports error: ' . $e->getMessage());
            $this->view('admin/reports', ['error' => 'Unable to load reports']);
        }
    }
    
    // System Admin Only Functions
    public function systemSettings() {
        AuthMiddleware::requireRole('system_admin');
        
        if ($this->isPost()) {
            try {
                // Handle system settings update
                $this->json(['success' => true, 'message' => 'System settings updated']);
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('admin/system_settings', ['active_page' => 'settings']);
        }
    }
    
    /** Convert DD-MM-YYYY to YYYY-MM-DD for DB storage. Returns today if blank/invalid. */
    private function toDbDate(string $raw, string $fallback = ''): ?string {
        $raw = trim($raw);
        if ($raw === '') return $fallback !== '' ? $fallback : null;
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // Already YYYY-MM-DD (fallback tolerance)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;
        return $fallback !== '' ? $fallback : null;
    }

    public function adminEntry() {
        AuthMiddleware::requireRole('admin');

        try {
            $db = Database::connect();
            $stmt = $db->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt2 = $db->query("SELECT id, name FROM projects WHERE status = 'active' ORDER BY name ASC");
            $projects = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $users = [];
            $projects = [];
        }

        if ($this->isPost()) {
            header('Content-Type: application/json');
            try {
                $type = $_POST['entry_type'] ?? '';
                $userId = intval($_POST['user_id'] ?? 0);
                $amount = floatval($_POST['amount'] ?? 0);

                if (!$userId || $amount <= 0 || !in_array($type, ['advance', 'expense'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid input']);
                    exit;
                }

                require_once __DIR__ . '/../helpers/LedgerHelper.php';

                if ($type === 'advance') {
                    $advType = trim($_POST['advance_type'] ?? 'General Advance');
                    $reason  = trim($_POST['reason'] ?? '');
                    $projectId = intval($_POST['project_id'] ?? 0) ?: null;
                    $advanceDate   = $this->toDbDate($_POST['advance_date'] ?? '', date('Y-m-d'));
                    $repaymentDate = $this->toDbDate($_POST['repayment_date'] ?? '');

                    $stmt = $db->prepare("INSERT INTO advances (user_id, project_id, type, amount, reason, requested_date, repayment_date, status, approved_by, approved_at, approved_amount, paid_by, paid_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', ?, NOW(), ?, ?, NOW(), NOW())");
                    $stmt->execute([$userId, $projectId, $advType, $amount, $reason, $advanceDate, $repaymentDate, $_SESSION['user_id'], $amount, $_SESSION['user_id']]);
                    $id = $db->lastInsertId();
                    LedgerHelper::recordEntry($userId, 'advance', 'advance', $id, $amount, 'credit');
                    echo json_encode(['success' => true, 'message' => 'Advance entry saved successfully']);
                } else {
                    $category    = trim($_POST['category'] ?? 'other');
                    $description = trim($_POST['description'] ?? '');
                    $expenseDate = $this->toDbDate($_POST['expense_date'] ?? '', date('Y-m-d'));
                    $projectId   = intval($_POST['project_id'] ?? 0) ?: null;

                    $stmt = $db->prepare("INSERT INTO expenses (user_id, project_id, category, amount, description, expense_date, status, approved_by, approved_at, approved_amount, paid_by, paid_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, NOW(), ?, ?, NOW(), NOW())");
                    $stmt->execute([$userId, $projectId, $category, $amount, $description, $expenseDate, $_SESSION['user_id'], $amount, $_SESSION['user_id']]);
                    $id = $db->lastInsertId();
                    LedgerHelper::recordEntry($userId, 'expense', 'expense', $id, $amount, 'debit');
                    echo json_encode(['success' => true, 'message' => 'Expense entry saved successfully']);
                }
            } catch (Exception $e) {
                error_log('Admin entry error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Failed to save entry']);
            }
            exit;
        }

        $this->view('admin/entry', [
            'users'       => $users,
            'projects'    => $projects,
            'active_page' => 'admin-entry'
        ]);
    }

    public function sampleCsv($type = 'advances') {
        AuthMiddleware::requireRole('admin');
        if (!in_array($type, ['advances', 'expenses'])) $type = 'advances';

        try {
            $db = Database::connect();
            $users    = $db->query("SELECT name FROM users WHERE status='active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
            $projects = $db->query("SELECT name FROM projects WHERE status='active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $users = ['John Smith', 'Jane Doe'];
            $projects = ['Project Alpha'];
        }

        $advanceTypes = ['Salary Advance', 'Travel Advance', 'Emergency Advance', 'Project Advance', 'General Advance'];
        $expenseCategories = ['travel', 'food', 'accommodation', 'office_supplies', 'communication', 'training', 'medical', 'other'];
        $today = date('d-m-Y');
        $future = date('d-m-Y', strtotime('+60 days'));
        $proj1 = $projects[0] ?? '';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_sample.csv"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');

        if ($type === 'advances') {
            fputcsv($out, ['employee_name', 'advance_type', 'amount', 'reason', 'advance_date', 'repayment_date', 'project_name']);
            // Sample data rows using real employees
            foreach (array_slice($users, 0, 5) as $i => $name) {
                fputcsv($out, [
                    $name,
                    $advanceTypes[$i % count($advanceTypes)],
                    (($i + 1) * 2000),
                    'Sample reason for advance',
                    $today,
                    $future,
                    $i === 1 ? $proj1 : ''
                ]);
            }
            // Reference block
            fputcsv($out, []);
            fputcsv($out, ['# --- REFERENCE ---']);
            fputcsv($out, ['# advance_type options:', implode(' | ', $advanceTypes)]);
            fputcsv($out, ['# project_name options (leave blank if none):', implode(' | ', $projects)]);
            fputcsv($out, ['# employee_name options:', implode(' | ', $users)]);
        } else {
            fputcsv($out, ['employee_name', 'category', 'amount', 'description', 'expense_date', 'project_name']);
            foreach (array_slice($users, 0, 5) as $i => $name) {
                fputcsv($out, [
                    $name,
                    $expenseCategories[$i % count($expenseCategories)],
                    (($i + 1) * 500),
                    'Sample expense description',
                    $today,
                    $i === 1 ? $proj1 : ''
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['# --- REFERENCE ---']);
            fputcsv($out, ['# category options:', implode(' | ', $expenseCategories)]);
            fputcsv($out, ['# project_name options (leave blank if none):', implode(' | ', $projects)]);
            fputcsv($out, ['# employee_name options:', implode(' | ', $users)]);
        }

        fclose($out);
        exit;
    }

    public function validateCsv() {
        AuthMiddleware::requireRole('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']); exit;
        }

        $type = $_POST['bulk_type'] ?? '';
        if (!in_array($type, ['advance', 'expense'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid type']); exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']); exit;
        }

        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            echo json_encode(['success' => false, 'error' => 'Only CSV files are accepted']); exit;
        }

        try {
            require_once __DIR__ . '/../helpers/CsvValidator.php';
            $db = Database::connect();
            $validator = new CsvValidator($db);
            $result = $validator->validate($_FILES['csv_file']['tmp_name'], $type);
            $result['success'] = true;
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('CSV validation error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Validation error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function adminBulkUpload() {
        AuthMiddleware::requireRole('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']); exit;
        }

        $type = $_POST['bulk_type'] ?? '';
        if (!in_array($type, ['advance', 'expense'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid type']); exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']); exit;
        }

        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            echo json_encode(['success' => false, 'error' => 'Only CSV files are accepted']); exit;
        }

        try {
            require_once __DIR__ . '/../helpers/CsvValidator.php';
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            $db = Database::connect();

            // Run validation first — reject if any hard errors exist
            $validator  = new CsvValidator($db);
            $validation = $validator->validate($_FILES['csv_file']['tmp_name'], $type);

            if (isset($validation['fatal'])) {
                echo json_encode(['success' => false, 'error' => $validation['fatal']]); exit;
            }

            $results = ['inserted' => 0, 'failed' => 0, 'rows' => []];

            // Re-parse file for insertion (validator already confirmed structure is valid)
            $handle  = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $rawHdr  = fgetcsv($handle);
            $headers = array_map(fn($h) => strtolower(trim(ltrim($h, "\xEF\xBB\xBF"))), $rawHdr);
            $rowNum  = 1;

            // Index validation results by row number for quick lookup
            $validationByRow = [];
            foreach ($validation['rows'] as $vr) {
                $validationByRow[$vr['row']] = $vr;
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (isset($row[0]) && str_starts_with(trim($row[0]), '#')) continue;
                if (count(array_filter(array_map('trim', $row))) === 0) continue;

                $data = array_combine($headers, array_pad($row, count($headers), ''));

                // Skip rows that had hard errors during validation
                $vRow = $validationByRow[$rowNum] ?? null;
                if ($vRow && $vRow['status'] === 'error') {
                    $results['failed']++;
                    $results['rows'][] = [
                        'row'      => $rowNum,
                        'status'   => 'failed',
                        'employee' => $data['employee_name'] ?? '',
                        'amount'   => $data['amount'] ?? '',
                        'reason'   => implode('; ', $vRow['errors']),
                    ];
                    continue;
                }

                $userId    = $validator->getUserId($data['employee_name'] ?? '');
                $projectId = $validator->getProjectId($data['project_name'] ?? '');
                $amount    = floatval($data['amount'] ?? 0);

                try {
                    if ($type === 'advance') {
                        $advType = trim($data['advance_type'] ?? 'General Advance') ?: 'General Advance';
                        $reason  = trim($data['reason'] ?? '') ?: 'Bulk entry by admin';
                        $advDate = $this->toDbDate($data['advance_date'] ?? '', date('Y-m-d'));
                        $repDate = $this->toDbDate($data['repayment_date'] ?? '');
                        $stmt = $db->prepare("INSERT INTO advances (user_id,project_id,type,amount,reason,requested_date,repayment_date,status,approved_by,approved_at,approved_amount,paid_by,paid_at,created_at) VALUES (?,?,?,?,?,?,?,'paid',?,NOW(),?,?,NOW(),NOW())");
                        $stmt->execute([$userId,$projectId,$advType,$amount,$reason,$advDate,$repDate,$_SESSION['user_id'],$amount,$_SESSION['user_id']]);
                        $id = $db->lastInsertId();
                        LedgerHelper::recordEntry($userId,'advance','advance',$id,$amount,'credit');
                    } else {
                        $category = trim($data['category'] ?? 'other') ?: 'other';
                        $desc     = trim($data['description'] ?? '') ?: 'Bulk entry by admin';
                        $expDate = $this->toDbDate($data['expense_date'] ?? '', date('Y-m-d'));
                        $stmt = $db->prepare("INSERT INTO expenses (user_id,project_id,category,amount,description,expense_date,status,approved_by,approved_at,approved_amount,paid_by,paid_at,created_at) VALUES (?,?,?,?,?,?,'paid',?,NOW(),?,?,NOW(),NOW())");
                        $stmt->execute([$userId,$projectId,$category,$amount,$desc,$expDate,$_SESSION['user_id'],$amount,$_SESSION['user_id']]);
                        $id = $db->lastInsertId();
                        LedgerHelper::recordEntry($userId,'expense','expense',$id,$amount,'debit');
                    }
                    $results['inserted']++;
                    $results['rows'][] = [
                        'row'      => $rowNum,
                        'status'   => 'success',
                        'employee' => $data['employee_name'] ?? '',
                        'amount'   => $amount,
                    ];
                } catch (Exception $re) {
                    $results['failed']++;
                    $results['rows'][] = [
                        'row'      => $rowNum,
                        'status'   => 'failed',
                        'employee' => $data['employee_name'] ?? '',
                        'amount'   => $data['amount'] ?? '',
                        'reason'   => $re->getMessage(),
                    ];
                }
            }
            fclose($handle);
            $results['success'] = true;
            echo json_encode($results);
        } catch (Exception $e) {
            error_log('Bulk upload error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function manageDepartments() {
        AuthMiddleware::requireRole('system_admin');
        
        try {
            $departmentModel = new Department();
            $departments = $departmentModel->getAll();
            
            $this->view('admin/manage_departments', [
                'departments' => $departments,
                'active_page' => 'departments'
            ]);
            
        } catch (Exception $e) {
            error_log('Manage departments error: ' . $e->getMessage());
            $this->view('admin/manage_departments', ['error' => 'Unable to load departments']);
        }
    }
    
    // Helper Methods
    private function getSystemAdminStats($db) {
        return [
            'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'total_departments' => $db->query("SELECT COUNT(*) FROM departments WHERE status = 'active'")->fetchColumn(),
            'pending_tasks' => $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn(),
            'pending_approvals' => $this->getTotalPendingApprovals($db),
            'today_attendance' => $db->query("SELECT COUNT(*) FROM attendance WHERE DATE(clock_in) = CURDATE()")->fetchColumn(),
            'system_alerts' => $this->getSystemAlerts($db)
        ];
    }
    
    private function getDepartmentAdminStats($db) {
        $deptId = $_SESSION['department_id'] ?? 1;
        
        $stmt1 = $db->prepare("SELECT COUNT(*) FROM users WHERE department_id = ? AND status = 'active'");
        $stmt1->execute([$deptId]);
        $departmentUsers = $stmt1->fetchColumn();
        
        $stmt2 = $db->prepare("SELECT COUNT(*) FROM tasks WHERE department_id = ? AND status = 'pending'");
        $stmt2->execute([$deptId]);
        $departmentTasks = $stmt2->fetchColumn();
        
        return [
            'department_users' => $departmentUsers,
            'department_tasks' => $departmentTasks,
            'pending_approvals' => $this->getDepartmentPendingApprovals($db, $deptId),
            'department_attendance' => $this->getDepartmentAttendanceToday($db, $deptId)
        ];
    }
    
    private function getSystemAdminOptions() {
        return [
            'create_users' => true,
            'manage_departments' => true,
            'system_settings' => true,
            'view_all_reports' => true,
            'manage_all_tasks' => true
        ];
    }
    
    private function getDepartmentAdminOptions() {
        return [
            'create_basic_users' => true,
            'manage_department_tasks' => true,
            'view_department_reports' => true,
            'approve_requests' => true
        ];
    }
    
    private function getTeamData($db, $isSystemAdmin) {
        if ($isSystemAdmin) {
            // System admin sees all teams
            $stmt = $db->query("SELECT d.name, COUNT(u.id) as user_count FROM departments d LEFT JOIN users u ON d.id = u.department_id WHERE u.status = 'active' GROUP BY d.id");
        } else {
            // Department admin sees only their team
            $stmt = $db->prepare("SELECT u.name, u.role, u.last_login FROM users u WHERE u.department_id = ? AND u.status = 'active'");
            $stmt->execute([$_SESSION['department_id']]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingLeaves($db) {
        $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.admin_approval = 'pending' ORDER BY l.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingExpenses($db) {
        $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.admin_approval = 'pending' ORDER BY e.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingAdvances($db) {
        $stmt = $db->query("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.admin_approval = 'pending' ORDER BY a.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTotalPendingApprovals($db) {
        $leaves = $db->query("SELECT COUNT(*) FROM leaves WHERE admin_approval = 'pending'")->fetchColumn();
        $expenses = $db->query("SELECT COUNT(*) FROM expenses WHERE admin_approval = 'pending'")->fetchColumn();
        $advances = $db->query("SELECT COUNT(*) FROM advances WHERE admin_approval = 'pending'")->fetchColumn();
        return $leaves + $expenses + $advances;
    }
    
    private function getDepartmentPendingApprovals($db, $deptId) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM (
                SELECT id FROM leaves l JOIN users u ON l.user_id = u.id WHERE u.department_id = ? AND l.admin_approval = 'pending'
                UNION ALL
                SELECT id FROM expenses e JOIN users u ON e.user_id = u.id WHERE u.department_id = ? AND e.admin_approval = 'pending'
                UNION ALL
                SELECT id FROM advances a JOIN users u ON a.user_id = u.id WHERE u.department_id = ? AND a.admin_approval = 'pending'
            ) as pending
        ");
        $stmt->execute([$deptId, $deptId, $deptId]);
        return $stmt->fetchColumn();
    }
    
    private function getDepartmentAttendanceToday($db, $deptId) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM attendance att JOIN users u ON att.user_id = u.id WHERE u.department_id = ? AND DATE(att.clock_in) = CURDATE()");
        $stmt->execute([$deptId]);
        return $stmt->fetchColumn();
    }
    
    private function getSystemAlerts($db) {
        $alerts = [];
        
        $overdueTasks = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status != 'completed'")->fetchColumn();
        if ($overdueTasks > 0) {
            $alerts[] = "{$overdueTasks} overdue tasks";
        }
        
        return $alerts;
    }
    
    private function getTaskCompletionReport($isSystemAdmin) {
        // Return task completion data based on admin type
        return [];
    }
    
    private function getAttendanceReport($isSystemAdmin) {
        // Return attendance data based on admin type
        return [];
    }
    
    private function getLeaveReport($isSystemAdmin) {
        // Return leave utilization data based on admin type
        return [];
    }
    
    private function getExpenseReport($isSystemAdmin) {
        // Return expense analysis data based on admin type
        return [];
    }
    
    private function ensureApprovalColumns($db) {
        try {
            $tables = ['leaves', 'expenses', 'advances'];
            
            foreach ($tables as $table) {
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_by INT DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_at DATETIME DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_comments TEXT DEFAULT NULL", "Alter table");
            }
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>
