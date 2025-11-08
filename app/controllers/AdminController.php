<?php
/**
 * Admin Controller - Department Admin vs System Admin
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleManager.php';
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
    
    public function createTask() {
        AuthMiddleware::requireRole('admin');
        
        if ($this->isPost()) {
            try {
                $taskModel = new Task();
                $taskData = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'assigned_to' => $_POST['assigned_to'],
                    'priority' => $_POST['priority'],
                    'due_date' => $_POST['due_date'],
                    'created_by' => $_SESSION['user_id'],
                    'department_id' => $_POST['department_id'] ?? $_SESSION['department_id']
                ];
                
                if ($taskModel->create($taskData)) {
                    $this->json(['success' => true, 'message' => 'Task created successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to create task']);
                }
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $userModel = new User();
            $isSystemAdmin = $_SESSION['role'] === 'system_admin';
            
            // System admin can assign to anyone, department admin only to their department
            if ($isSystemAdmin) {
                $users = $userModel->getUsersByRole(['user']);
            } else {
                $users = $userModel->getByDepartment($_SESSION['department_id']);
            }
            
            $this->view('admin/create_task', [
                'users' => $users,
                'is_system_admin' => $isSystemAdmin,
                'active_page' => 'tasks'
            ]);
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
                $tasks = $taskModel->getByDepartment($_SESSION['department_id']);
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
        $deptId = $_SESSION['department_id'];
        return [
            'department_users' => $db->prepare("SELECT COUNT(*) FROM users WHERE department_id = ? AND status = 'active'")->execute([$deptId]) ? $db->fetchColumn() : 0,
            'department_tasks' => $db->prepare("SELECT COUNT(*) FROM tasks WHERE department_id = ? AND status = 'pending'")->execute([$deptId]) ? $db->fetchColumn() : 0,
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
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_by INT DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_at DATETIME DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_comments TEXT DEFAULT NULL");
            }
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>