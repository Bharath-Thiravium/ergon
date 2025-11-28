<?php
/**
 * Owner Controller - Complete Role-Based Implementation
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleManager.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/Advance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Department.php';

class OwnerController extends Controller {
    
    public function dashboard() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            // Get comprehensive statistics
            $stats = [
                'total_users' => $this->getTotalUsers($db),
                'total_admins' => $this->getTotalAdmins($db),
                'total_departments' => $this->getTotalDepartments($db),
                'pending_final_approvals' => $this->getPendingFinalApprovals($db),
                'active_tasks' => $this->getActiveTasks($db),
                'today_attendance' => $this->getTodayAttendance($db),
                'monthly_productivity' => $this->getMonthlyProductivity($db),
                'pending_leaves' => $this->getPendingLeavesCount($db),
                'pending_expenses' => $this->getPendingExpensesCount($db),
                // Project stats for dashboard cards
                'active_projects' => $this->getActiveProjectsCount($db),
                'completed_tasks' => $this->getCompletedTasksCount($db),
                'avg_progress' => $this->getAverageProgress($db),
                'in_progress' => $this->getInProgressTasksCount($db),
                'pending' => $this->getPendingTasksCount($db),
                'completion_rate' => $this->getCompletionRate($db),
                // Delayed tasks stats
                'overdue_tasks' => $this->getOverdueTasksCount($db),
                'due_this_week' => $this->getDueThisWeekCount($db),
                'due_tomorrow' => $this->getDueTomorrowCount($db),
                'rescheduled' => $this->getRescheduledTasksCount($db),
                'critical' => $this->getCriticalTasksCount($db),
                'ontime_rate' => $this->getOntimeRate($db),
                // Approval stats
                'leave_requests' => $this->getPendingLeavesCount($db),
                'expense_claims' => $this->getPendingExpensesCount($db),
                'advance_requests' => $this->getPendingAdvancesCount($db)
            ];
            
            // Get items requiring owner's final approval
            $finalApprovals = [
                'leaves' => $this->getPendingLeaves($db, 'final'),
                'expenses' => $this->getPendingExpenses($db, 'final'),
                'advances' => $this->getPendingAdvances($db, 'final')
            ];
            
            // Get system alerts
            $alerts = $this->getSystemAlerts($db);
            
            // Debug: Log the stats to see what's being fetched
            error_log('Owner Dashboard Stats: ' . json_encode($stats));
            
            // Add debug info to view
            $stats['debug_info'] = [
                'active_projects_query' => $this->getActiveProjectsCount($db),
                'completed_tasks_query' => $this->getCompletedTasksCount($db),
                'in_progress_query' => $this->getInProgressTasksCount($db),
                'pending_query' => $this->getPendingTasksCount($db)
            ];
            
            // Force clear any cached data
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            $this->view('owner/dashboard', [
                'data' => [
                    'stats' => $stats,
                    'final_approvals' => $finalApprovals,
                    'alerts' => $alerts,
                    'recent_activities' => [] // Add empty activities for now
                ],
                'active_page' => 'dashboard'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner dashboard error: ' . $e->getMessage());
            $this->view('owner/dashboard', ['error' => 'Unable to load dashboard data']);
        }
    }
    
    public function approvals() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            // Only get items that need final owner approval
            $pendingLeaves = $this->getPendingLeaves($db, 'final');
            $pendingExpenses = $this->getPendingExpenses($db, 'final');
            $pendingAdvances = $this->getPendingAdvances($db, 'final');
            
            $this->view('owner/approvals', [
                'leaves' => $pendingLeaves,
                'expenses' => $pendingExpenses,
                'advances' => $pendingAdvances,
                'active_page' => 'approvals'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner approvals error: ' . $e->getMessage());
            $this->view('owner/approvals', ['error' => 'Unable to load approvals']);
        }
    }
    
    public function createUser() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $userModel = new User();
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
            $this->view('owner/create_user', ['active_page' => 'users']);
        }
    }
    
    public function manageUsers() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $userModel = new User();
            $users = $userModel->getAll();
            
            $this->view('owner/manage_users', [
                'users' => $users,
                'active_page' => 'users'
            ]);
            
        } catch (Exception $e) {
            error_log('Manage users error: ' . $e->getMessage());
            $this->view('owner/manage_users', ['error' => 'Unable to load users']);
        }
    }
    
    public function assignRole() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $userModel = new User();
                $userId = $_POST['user_id'];
                $newRole = $_POST['role'];
                
                if ($userModel->update($userId, ['role' => $newRole])) {
                    $this->json(['success' => true, 'message' => 'Role updated successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to update role']);
                }
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
    
    public function finalApprove() {
        AuthMiddleware::requireRole('owner');
        
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
            
            $status = $action === 'approve' ? 'approved' : 'rejected';
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$status, $action, $_SESSION['user_id'], $comments, $id]);
            
            if ($result) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' ' . $action . 'd successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to ' . $action . ' ' . $type]);
            }
            
        } catch (Exception $e) {
            error_log('Final approval error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function systemSettings() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $settings = $_POST['settings'];
                // Update system settings logic here
                $this->json(['success' => true, 'message' => 'Settings updated successfully']);
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('owner/settings', ['active_page' => 'settings']);
        }
    }
    
    public function analytics() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            $analytics = [
                'user_growth' => $this->getUserGrowthData($db),
                'task_completion' => $this->getTaskCompletionData($db),
                'attendance_trends' => $this->getAttendanceTrends($db),
                'department_performance' => $this->getDepartmentPerformance($db)
            ];
            
            $this->view('owner/analytics', [
                'analytics' => $analytics,
                'active_page' => 'analytics'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner analytics error: ' . $e->getMessage());
            $this->view('owner/analytics', ['error' => 'Unable to load analytics']);
        }
    }
    
    // Legacy methods for backward compatibility
    public function approveRequest() {
        $this->finalApprove();
    }
    
    public function rejectRequest() {
        if ($this->isPost()) {
            $_POST['action'] = 'reject';
            $this->finalApprove();
        }
    }
    
    public function viewApproval($type, $id) {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Item not found');
            }
            
            $this->view('owner/view_approval', [
                'type' => $type,
                'item' => $item,
                'active_page' => 'approvals'
            ]);
            
        } catch (Exception $e) {
            error_log('View approval error: ' . $e->getMessage());
            $this->redirect('/owner/approvals');
        }
    }
    
    public function deleteApproval($type, $id) {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->redirect('/owner/approvals');
            return;
        }
        
        try {
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("DELETE FROM leaves WHERE id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("DELETE FROM advances WHERE id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            if ($stmt->execute([$id])) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' deleted successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to delete ' . $type]);
            }
            
        } catch (Exception $e) {
            error_log('Delete approval error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Helper methods
    private function getTotalUsers($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        return $stmt->fetchColumn();
    }
    
    private function getTotalAdmins($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'system_admin') AND status = 'active'");
        return $stmt->fetchColumn();
    }
    
    private function getTotalDepartments($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM departments WHERE status = 'active'");
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function getPendingFinalApprovals($db) {
        $leaves = $db->query("SELECT COUNT(*) FROM leaves WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        $expenses = $db->query("SELECT COUNT(*) FROM expenses WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        $advances = $db->query("SELECT COUNT(*) FROM advances WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        return $leaves + $expenses + $advances;
    }
    
    private function getActiveTasks($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')");
        return $stmt->fetchColumn();
    }
    
    private function getTodayAttendance($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE DATE(clock_in) = CURDATE()");
        return $stmt->fetchColumn();
    }
    
    private function getMonthlyProductivity($db) {
        // Calculate productivity score based on task completion
        return 85; // Placeholder
    }
    
    private function getPendingLeaves($db, $level = 'all') {
        if ($level === 'final') {
            $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.admin_approval = 'approved' AND l.owner_approval = 'pending' ORDER BY l.created_at DESC");
        } else {
            $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC");
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingExpenses($db, $level = 'all') {
        if ($level === 'final') {
            $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.admin_approval = 'approved' AND e.owner_approval = 'pending' ORDER BY e.created_at DESC");
        } else {
            $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingAdvances($db, $level = 'all') {
        if ($level === 'final') {
            $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.admin_approval = 'approved' AND a.owner_approval = 'pending' ORDER BY a.created_at DESC");
        } else {
            $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC");
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getSystemAlerts($db) {
        $alerts = [];
        
        // Check for inactive users
        $inactiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
        if ($inactiveUsers > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inactiveUsers} inactive users need attention"
            ];
        }
        
        // Check for overdue tasks
        $overdueTasks = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status != 'completed'")->fetchColumn();
        if ($overdueTasks > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$overdueTasks} tasks are overdue"
            ];
        }
        
        return $alerts;
    }
    
    private function getUserGrowthData($db) {
        // Return user growth data for charts
        return [];
    }
    
    private function getTaskCompletionData($db) {
        // Return task completion statistics
        return [];
    }
    
    private function getAttendanceTrends($db) {
        // Return attendance trend data
        return [];
    }
    
    private function getDepartmentPerformance($db) {
        // Return department-wise performance metrics
        return [];
    }
    
    private function getPendingLeavesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getPendingExpensesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM expenses WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getPendingAdvancesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM advances WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getActiveProjectsCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
            $count = $stmt->fetchColumn();
            error_log('Active Projects from projects table: ' . $count);
            if ($count > 0) return $count;
        } catch (Exception $e) {
            error_log('Projects table error: ' . $e->getMessage());
        }
        
        $stmt = $db->query("SELECT COUNT(DISTINCT project_name) FROM tasks WHERE project_name IS NOT NULL AND project_name != '' AND status != 'completed'");
        $count = $stmt->fetchColumn() ?: 0;
        error_log('Active Projects from tasks table: ' . $count);
        return $count;
    }
    
    private function getCompletedTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
        $count = $stmt->fetchColumn() ?: 0;
        error_log('Completed Tasks: ' . $count);
        return $count;
    }
    
    private function getAverageProgress($db) {
        $stmt = $db->query("
            SELECT AVG(
                (SELECT COUNT(*) FROM tasks t2 WHERE t2.project_name = t1.project_name AND t2.status = 'completed') * 100.0 /
                (SELECT COUNT(*) FROM tasks t3 WHERE t3.project_name = t1.project_name)
            ) as avg_progress
            FROM (SELECT DISTINCT project_name FROM tasks WHERE project_name IS NOT NULL AND project_name != '') t1
        ");
        return round($stmt->fetchColumn() ?: 0);
    }
    
    private function getInProgressTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('in_progress', 'assigned')");
        $count = $stmt->fetchColumn();
        error_log('In Progress Tasks: ' . $count);
        return $count;
    }
    
    private function getPendingTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'not_started')");
        $count = $stmt->fetchColumn();
        error_log('Pending Tasks: ' . $count);
        return $count;
    }
    
    private function getCompletionRate($db) {
        $stmt = $db->query("
            SELECT 
                (COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*)) as completion_rate
            FROM tasks
        ");
        return round($stmt->fetchColumn() ?: 0);
    }
    
    private function getOverdueTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date < CURDATE() OR deadline < CURDATE()) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getDueThisWeekCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getDueTomorrowCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (DATE(due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR DATE(deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getRescheduledTasksCount($db) {
        // Count tasks that have been updated multiple times (approximation)
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE updated_at != created_at AND status != 'completed'");
        return $stmt->fetchColumn();
    }
    
    private function getCriticalTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE priority = 'high' AND due_date < DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND status != 'completed'");
        return $stmt->fetchColumn();
    }
    
    private function getOntimeRate($db) {
        $stmt = $db->query("
            SELECT 
                (COUNT(CASE WHEN status = 'completed' AND updated_at <= due_date THEN 1 END) * 100.0 / 
                 COUNT(CASE WHEN status = 'completed' THEN 1 END)) as ontime_rate
            FROM tasks 
            WHERE status = 'completed' AND due_date IS NOT NULL
        ");
        return round($stmt->fetchColumn() ?: 0);
    }
    
    private function ensureApprovalColumns($db) {
        try {
            // Add missing columns for multi-level approval
            $tables = ['leaves', 'expenses', 'advances'];
            
            foreach ($tables as $table) {
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_by INT DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_at DATETIME DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_comments TEXT DEFAULT NULL");
                
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_by INT DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_at DATETIME DEFAULT NULL");
                $db->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_comments TEXT DEFAULT NULL");
            }
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>