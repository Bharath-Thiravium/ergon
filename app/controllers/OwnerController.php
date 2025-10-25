<?php
/**
 * Owner Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

class OwnerController extends Controller {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function dashboard() {
        AuthMiddleware::requireRole('owner');
        
        $stats = $this->getKPIStats();
        $pending_approvals = $this->getPendingApprovals();
        $recent_activities = $this->getRecentActivities();
        
        $data = [
            'stats' => $stats,
            'pending_approvals' => $pending_approvals,
            'recent_activities' => $recent_activities
        ];
        
        $this->view('owner/dashboard', $data);
    }
    
    public function approvals() {
        AuthMiddleware::requireRole('owner');
        
        $data = ['approvals' => []];
        $this->view('owner/approvals', $data);
    }
    
    private function getKPIStats() {
        try {
            $stats = [
                'total_users' => 0,
                'active_tasks' => 0,
                'pending_leaves' => 0,
                'pending_expenses' => 0
            ];
            
            // Get user count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['total_users'] = $result['count'] ?? 0;
            
            // Try to get other stats if tables exist
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['active_tasks'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                // Tasks table doesn't exist
            }
            
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM leaves WHERE status = 'Pending'");
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['pending_leaves'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                // Leaves table doesn't exist
            }
            
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
            error_log("Owner dashboard stats error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_tasks' => 0,
                'pending_leaves' => 0,
                'pending_expenses' => 0
            ];
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
    
    private function getRecentActivities() {
        try {
            $stmt = $this->db->prepare("SELECT 'Login' as action, 'User logged in' as description, last_login as created_at FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 5");
            $stmt->execute();
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($activity) {
                return [
                    'module' => 'Users',
                    'action' => $activity['action'],
                    'description' => $activity['description'],
                    'created_at' => $activity['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }, $activities);
        } catch (Exception $e) {
            return [
                ['module' => 'System', 'action' => 'Status', 'description' => 'System operational', 'created_at' => date('Y-m-d H:i:s')]
            ];
        }
    }
}
?>