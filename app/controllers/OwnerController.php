<?php
class OwnerController {
    
    public function approvals() {
        $data = [
            'approvals' => [
                ['type' => 'Leave', 'requested_by_name' => 'John Doe', 'remarks' => 'Annual leave for vacation', 'count' => '3 days', 'created_at' => '2024-01-15'],
                ['type' => 'Leave', 'requested_by_name' => 'Jane Smith', 'remarks' => 'Sick leave', 'count' => '2 days', 'created_at' => '2024-01-20'],
                ['type' => 'Expense', 'requested_by_name' => 'Mike Johnson', 'remarks' => 'Client meeting lunch', 'count' => '$45.50', 'created_at' => '2024-01-10'],
                ['type' => 'Expense', 'requested_by_name' => 'Sarah Wilson', 'remarks' => 'Travel to conference', 'count' => '$120.00', 'created_at' => '2024-01-12']
            ]
        ];
        
        include __DIR__ . '/../../views/owner/approvals.php';
    }
    
    public function dashboard() {
        $data = [
            'stats' => [
                'total_users' => 25,
                'active_tasks' => 18,
                'pending_leaves' => 3,
                'pending_expenses' => 5
            ],
            'pending_approvals' => [
                ['type' => 'Leave Requests', 'count' => 3],
                ['type' => 'Expense Claims', 'count' => 5],
                ['type' => 'Advance Requests', 'count' => 2]
            ],
            'recent_activities' => [
                ['action' => 'New User Registration', 'description' => 'John Doe joined the system', 'created_at' => '2024-01-15 10:30:00'],
                ['action' => 'Task Completed', 'description' => 'Project Alpha milestone reached', 'created_at' => '2024-01-15 09:15:00'],
                ['action' => 'Leave Approved', 'description' => 'Annual leave approved for Jane Smith', 'created_at' => '2024-01-14 16:45:00']
            ]
        ];
        
        include __DIR__ . '/../../views/owner/dashboard.php';
    }
}
?>
