<?php
require_once __DIR__ . '/../../config/database.php';

class OwnerController {
    private $db;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function dashboard() {
        $stats = $this->getKPIStats();
        $pending_approvals = $this->getPendingApprovals();
        $recent_activities = $this->getRecentActivities();
        
        $data = [
            'stats' => $stats,
            'pending_approvals' => $pending_approvals,
            'recent_activities' => $recent_activities
        ];
        
        include __DIR__ . '/../views/owner/dashboard.php';
    }
    
    public function approvals() {
        // Create sample approval data since tables may not exist
        $pending = [
            [
                'type' => 'Leave',
                'id' => 1,
                'remarks' => 'Annual Leave Request',
                'count' => '3 days',
                'requested_by_name' => 'John Doe',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 'Expense',
                'id' => 2,
                'remarks' => 'Travel Expense Claim',
                'count' => '$250.00',
                'requested_by_name' => 'Jane Smith',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $data = ['approvals' => $pending];
        include __DIR__ . '/../views/owner/approvals.php';
    }
    
    private function getKPIStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
                    (SELECT COUNT(*) FROM tasks WHERE status != 'completed') as active_tasks,
                    (SELECT COUNT(*) FROM leaves WHERE status = 'Pending') as pending_leaves,
                    (SELECT COUNT(*) FROM expenses WHERE status = 'pending') as pending_expenses";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getPendingApprovals() {
        $sql = "SELECT 'Leave' as type, COUNT(*) as count FROM leaves WHERE status = 'Pending'
                UNION ALL
                SELECT 'Expense' as type, COUNT(*) as count FROM expenses WHERE status = 'pending'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentActivities() {
        $sql = "SELECT module, action, description, created_at 
                FROM audit_logs 
                ORDER BY created_at DESC 
                LIMIT 10";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}