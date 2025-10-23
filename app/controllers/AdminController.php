<?php
require_once __DIR__ . '/../../config/database.php';

class AdminController {
    private $db;
    
    public function __construct() {
        RoleMiddleware::requireRole(['admin', 'owner']);
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function dashboard() {
        $stats = $this->getAdminStats();
        $recent_tasks = $this->getRecentTasks();
        $pending_approvals = $this->getPendingApprovals();
        
        $data = [
            'stats' => $stats,
            'recent_tasks' => $recent_tasks,
            'pending_approvals' => $pending_approvals
        ];
        
        include __DIR__ . '/../views/admin/dashboard.php';
    }
    
    private function getAdminStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
                    (SELECT COUNT(*) FROM tasks WHERE status != 'completed') as active_tasks,
                    (SELECT COUNT(*) FROM leaves WHERE status = 'Pending') as pending_leaves,
                    (SELECT COUNT(*) FROM expenses WHERE status = 'pending') as pending_expenses,
                    (SELECT COUNT(*) FROM tasks WHERE deadline < NOW() AND status != 'completed') as overdue_tasks";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getRecentTasks() {
        $sql = "SELECT t.*, u.name as assigned_to_name 
                FROM tasks t 
                JOIN users u ON t.assigned_to = u.id 
                ORDER BY t.created_at DESC 
                LIMIT 5";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingApprovals() {
        $sql = "SELECT 'Leave' as type, COUNT(*) as count FROM leaves WHERE status = 'Pending'
                UNION ALL
                SELECT 'Expense' as type, COUNT(*) as count FROM expenses WHERE status = 'pending'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}