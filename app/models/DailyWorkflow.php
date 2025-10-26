<?php

class DailyWorkflow {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    // Check if user has submitted morning plan
    public function hasMorningPlan($userId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("SELECT morning_submitted FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch();
        return $result && $result['morning_submitted'];
    }
    
    // Check if user has submitted evening update
    public function hasEveningUpdate($userId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("SELECT evening_updated FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch();
        return $result && $result['evening_updated'];
    }
    
    // Get user's daily plans
    public function getDailyPlans($userId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM daily_plans WHERE user_id = ? AND plan_date = ? ORDER BY priority DESC, created_at ASC");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll();
    }
    
    // Get workflow status
    public function getWorkflowStatus($userId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM daily_workflow_status WHERE user_id = ? AND workflow_date = ?");
        $stmt->execute([$userId, $date]);
        return $stmt->fetch();
    }
    
    // Get team productivity stats
    public function getTeamStats($date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT u.name, u.id, dws.*, 
                   (SELECT COUNT(*) FROM daily_plans dp WHERE dp.user_id = u.id AND dp.plan_date = ?) as total_tasks,
                   (SELECT COUNT(*) FROM daily_plans dp WHERE dp.user_id = u.id AND dp.plan_date = ? AND dp.status = 'completed') as completed_tasks
            FROM users u 
            LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date = ?
            WHERE u.role = 'user' AND u.status = 'active'
            ORDER BY dws.productivity_score DESC NULLS LAST
        ");
        $stmt->execute([$date, $date, $date]);
        return $stmt->fetchAll();
    }
    
    // Get delayed/blocked tasks
    public function getDelayedTasks($date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT dp.*, u.name as user_name 
            FROM daily_plans dp 
            JOIN users u ON dp.user_id = u.id 
            WHERE dp.status IN ('blocked', 'pending') AND dp.plan_date <= ? 
            ORDER BY dp.plan_date ASC, dp.priority DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }
    
    // Calculate productivity metrics
    public function calculateProductivityScore($plannedHours, $actualHours, $completedTasks, $totalTasks) {
        if ($plannedHours == 0 || $totalTasks == 0) return 0;
        
        $timeEfficiency = min(100, ($actualHours / $plannedHours) * 100);
        $taskCompletion = ($completedTasks / $totalTasks) * 100;
        
        // Weighted average: 60% task completion, 40% time efficiency
        return ($taskCompletion * 0.6) + ($timeEfficiency * 0.4);
    }
}