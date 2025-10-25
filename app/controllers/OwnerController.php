<?php
/**
 * Owner Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class OwnerController extends Controller {
    
    public function dashboard() {
        // Use AuthMiddleware for proper authentication
        AuthMiddleware::requireRole('owner');
        
        // Sample data for dashboard
        $data = [
            'stats' => [
                'total_users' => 5,
                'active_tasks' => 12,
                'pending_leaves' => 3,
                'pending_expenses' => 2
            ],
            'pending_approvals' => [
                ['type' => 'Leave', 'count' => 3],
                ['type' => 'Expense', 'count' => 2]
            ],
            'recent_activities' => [
                ['module' => 'Users', 'action' => 'Login', 'description' => 'User logged in', 'created_at' => date('Y-m-d H:i:s')]
            ]
        ];
        
        $this->view('owner/dashboard', $data);
    }
    
    public function approvals() {
        AuthMiddleware::requireRole('owner');
        
        $data = ['approvals' => []];
        $this->view('owner/approvals', $data);
    }
}
?>