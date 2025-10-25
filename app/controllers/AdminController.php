<?php
/**
 * Admin Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

class AdminController extends Controller {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function dashboard() {
        AuthMiddleware::requireAuth();
        
        // Check if user is admin or owner
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: /ergon/dashboard');
            exit;
        }
        
        $stats = $this->getAdminStats();
        $recent_tasks = $this->getRecentTasks();
        $pending_approvals = $this->getPendingApprovals();
        
        $data = [
            'stats' => $stats,
            'recent_tasks' => $recent_tasks,
            'pending_approvals' => $pending_approvals
        ];
        
        $this->view('admin/dashboard', $data);
    }
    
    private function getAdminStats() {
        try {
            // Get basic user stats
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_users FROM users WHERE status = 'active'");
            $stmt->execute();
            $userStats = $stmt->fetch();
            
            $stats = [
                'total_users' => $userStats['total_users'] ?? 0,
                'active_tasks' => 0,
                'pending_leaves' => 0,
                'pending_expenses' => 0,
                'overdue_tasks' => 0
            ];
            
            // Try to get task stats if table exists
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['active_tasks'] = $result['count'] ?? 0;
                
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tasks WHERE deadline < NOW() AND status != 'completed'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['overdue_tasks'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                // Tasks table doesn't exist
            }
            
            // Try to get leave stats if table exists
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM leaves WHERE status = 'Pending'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['pending_leaves'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                // Leaves table doesn't exist
            }
            
            // Try to get expense stats if table exists
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['pending_expenses'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                // Expenses table doesn't exist
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Admin stats error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_tasks' => 0,
                'pending_leaves' => 0,
                'pending_expenses' => 0,
                'overdue_tasks' => 0
            ];
        }
    }
    
    private function getRecentTasks() {
        try {
            $stmt = $this->db->prepare("SELECT t.*, u.name as assigned_to_name 
                                      FROM tasks t 
                                      JOIN users u ON t.assigned_to = u.id 
                                      ORDER BY t.created_at DESC 
                                      LIMIT 5");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Tasks table doesn't exist
            return [];
        }
    }
    
    private function getPendingApprovals() {
        $approvals = [];
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM leaves WHERE status = 'Pending'");
            $stmt->execute();
            $result = $stmt->fetch();
            $approvals[] = ['type' => 'Leave', 'count' => $result['count'] ?? 0];
        } catch (Exception $e) {
            $approvals[] = ['type' => 'Leave', 'count' => 0];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
            $stmt->execute();
            $result = $stmt->fetch();
            $approvals[] = ['type' => 'Expense', 'count' => $result['count'] ?? 0];
        } catch (Exception $e) {
            $approvals[] = ['type' => 'Expense', 'count' => 0];
        }
        
        return $approvals;
    }
}
?>