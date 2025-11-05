<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class AdminController extends Controller {
    
    public function dashboard() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get dashboard stats
            $stats = $this->getDashboardStats($db);
            $pendingApprovals = $this->getPendingApprovals($db);
            
            $data = [
                'stats' => $stats,
                'pending_approvals' => $pendingApprovals,
                'active_page' => 'dashboard'
            ];
            
            $this->view('admin/dashboard', $data);
        } catch (Exception $e) {
            error_log('Admin dashboard error: ' . $e->getMessage());
            $data = [
                'stats' => $this->getDefaultStats(),
                'pending_approvals' => ['leaves' => [], 'expenses' => [], 'advances' => []],
                'active_page' => 'dashboard',
                'error' => 'Unable to load dashboard data'
            ];
            $this->view('admin/dashboard', $data);
        }
    }
    
    private function getDashboardStats($db) {
        try {
            // Total users
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
            $totalUsers = $stmt->fetch()['count'] ?? 0;
            
            // Active tasks
            $stmt = $db->query("SELECT COUNT(*) as count FROM tasks WHERE status IN ('pending', 'in_progress')");
            $activeTasks = $stmt->fetch()['count'] ?? 0;
            
            // Pending leaves
            $stmt = $db->query("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'");
            $pendingLeaves = $stmt->fetch()['count'] ?? 0;
            
            // Pending expenses
            $stmt = $db->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
            $pendingExpenses = $stmt->fetch()['count'] ?? 0;
            
            return [
                'total_users' => $totalUsers,
                'active_tasks' => $activeTasks,
                'pending_leaves' => $pendingLeaves,
                'pending_expenses' => $pendingExpenses
            ];
        } catch (Exception $e) {
            return $this->getDefaultStats();
        }
    }
    
    private function getPendingApprovals($db) {
        try {
            $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l LEFT JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC LIMIT 10");
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e LEFT JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC LIMIT 10");
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->query("SELECT a.*, u.name as user_name FROM advances a LEFT JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC LIMIT 10");
            $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['leaves' => $leaves, 'expenses' => $expenses, 'advances' => $advances];
        } catch (Exception $e) {
            error_log('Error fetching pending approvals: ' . $e->getMessage());
            return ['leaves' => [], 'expenses' => [], 'advances' => []];
        }
    }
    
    private function getDefaultStats() {
        return [
            'total_users' => 0,
            'active_tasks' => 0,
            'pending_leaves' => 0,
            'pending_expenses' => 0
        ];
    }
}
?>