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
            
            // Get basic statistics only
            $stats = [
                'total_users' => $this->getTotalUsers($db),
                'pending_leaves' => $this->getPendingLeavesCount($db),
                'pending_expenses' => $this->getPendingExpensesCount($db),
                'active_tasks' => $this->getActiveTasks($db)
            ];
            
            $this->view('owner/dashboard', [
                'data' => [
                    'stats' => $stats,
                    'final_approvals' => [],
                    'alerts' => [],
                    'recent_activities' => []
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
            
            // Get all pending requests for owner approval
            $pendingLeaves = $this->getPendingLeaves($db);
            $pendingExpenses = $this->getPendingExpenses($db);
            $pendingAdvances = $this->getPendingAdvances($db);
            
            $this->view('owner/approvals', [
                'leaves' => $pendingLeaves,
                'expenses' => $pendingExpenses,
                'advances' => $pendingAdvances,
                'active_page' => 'approvals'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner approvals error: ' . $e->getMessage());
            $this->view('owner/approvals', ['error' => 'Unable to load approvals: ' . $e->getMessage()]);
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
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $_POST['type'];
            $id = (int)$_POST['id'];
            
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' approved successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to approve ' . $type . ' or already processed']);
            }
            
        } catch (Exception $e) {
            error_log('Owner approve request error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function rejectRequest() {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $_POST['type'];
            $id = (int)$_POST['id'];
            $reason = $_POST['remarks'] ?? 'Rejected by owner';
            
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$reason, $_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' rejected successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to reject ' . $type . ' or already processed']);
            }
            
        } catch (Exception $e) {
            error_log('Owner reject request error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
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
        // Always fetch pending leaves for owner approval
        $stmt = $db->prepare("SELECT l.*, u.name as user_name, l.leave_type as type FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingExpenses($db, $level = 'all') {
        // Always fetch pending expenses for owner approval
        $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingAdvances($db, $level = 'all') {
        // Always fetch pending advances for owner approval
        $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC");
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
            if ($count > 0) return $count;
        } catch (Exception $e) {
            // Projects table doesn't exist, fall back to tasks
        }
        
        try {
            $stmt = $db->query("SELECT COUNT(DISTINCT project_name) FROM tasks WHERE project_name IS NOT NULL AND project_name != '' AND status != 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCompletedTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getAverageProgress($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks");
            $totalTasks = $stmt->fetchColumn();
            if ($totalTasks == 0) return 0;
            
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            $completedTasks = $stmt->fetchColumn();
            
            return round(($completedTasks / $totalTasks) * 100);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getInProgressTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('in_progress', 'assigned')");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getPendingTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'not_started')");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCompletionRate($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks");
            $totalTasks = $stmt->fetchColumn();
            if ($totalTasks == 0) return 0;
            
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            $completedTasks = $stmt->fetchColumn();
            
            return round(($completedTasks / $totalTasks) * 100);
        } catch (Exception $e) {
            return 0;
        }
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
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_by INT DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_at DATETIME DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_comments TEXT DEFAULT NULL", "Alter table");
                
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_by INT DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_at DATETIME DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_comments TEXT DEFAULT NULL", "Alter table");
            }
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>
