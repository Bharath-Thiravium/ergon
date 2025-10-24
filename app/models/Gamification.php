<?php
require_once __DIR__ . '/../../config/database.php';

class Gamification {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function awardPoints($userId, $points, $reason, $taskId = null) {
        $query = "INSERT INTO user_points (user_id, points, reason, task_id, earned_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$userId, $points, $reason, $taskId]);
        
        if ($result) {
            $this->checkAchievements($userId);
        }
        
        return $result;
    }
    
    public function getUserStats($userId) {
        $query = "SELECT 
                    SUM(points) as total_points,
                    COUNT(*) as total_activities,
                    (SELECT COUNT(*) FROM user_achievements WHERE user_id = ?) as achievements_count,
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed') as completed_tasks,
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND deadline >= created_at AND status = 'completed') as on_time_tasks
                  FROM user_points 
                  WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getLeaderboard($period = 'month', $limit = 10) {
        $dateFilter = match($period) {
            'week' => 'AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
            'month' => 'AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
            'year' => 'AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)',
            default => ''
        };
        
        $query = "SELECT u.name, u.id, SUM(up.points) as total_points,
                         COUNT(DISTINCT up.task_id) as tasks_completed,
                         AVG(up.points) as avg_points
                  FROM users u
                  JOIN user_points up ON u.id = up.user_id
                  WHERE u.status = 'active' {$dateFilter}
                  GROUP BY u.id, u.name
                  ORDER BY total_points DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function checkAchievements($userId) {
        $achievements = [
            'first_task' => ['condition' => 'completed_tasks >= 1', 'points' => 50, 'title' => 'First Steps'],
            'task_master' => ['condition' => 'completed_tasks >= 10', 'points' => 200, 'title' => 'Task Master'],
            'speed_demon' => ['condition' => 'on_time_completion_rate >= 0.9', 'points' => 300, 'title' => 'Speed Demon'],
            'consistency_king' => ['condition' => 'streak_days >= 7', 'points' => 400, 'title' => 'Consistency King'],
            'zero_breach' => ['condition' => 'sla_breach_count = 0 AND completed_tasks >= 5', 'points' => 500, 'title' => 'Zero Breach Hero']
        ];
        
        $stats = $this->getUserStats($userId);
        
        foreach ($achievements as $key => $achievement) {
            // Check if user already has this achievement
            $checkQuery = "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_key = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$userId, $key]);
            
            if (!$checkStmt->fetch()) {
                // Evaluate achievement condition (simplified)
                if ($this->evaluateAchievement($userId, $achievement['condition'])) {
                    $this->grantAchievement($userId, $key, $achievement);
                }
            }
        }
    }
    
    private function evaluateAchievement($userId, $condition) {
        // Simplified achievement evaluation
        $stats = $this->getUserStats($userId);
        
        // Parse condition and evaluate
        if (strpos($condition, 'completed_tasks >= 1') !== false) {
            return $stats['completed_tasks'] >= 1;
        }
        if (strpos($condition, 'completed_tasks >= 10') !== false) {
            return $stats['completed_tasks'] >= 10;
        }
        
        return false;
    }
    
    private function grantAchievement($userId, $key, $achievement) {
        $query = "INSERT INTO user_achievements (user_id, achievement_key, title, points, earned_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$userId, $key, $achievement['title'], $achievement['points']]);
        
        if ($result) {
            // Award bonus points
            $this->awardPoints($userId, $achievement['points'], "Achievement: {$achievement['title']}");
            
            // Send notification
            require_once __DIR__ . '/../helpers/NotificationHelper.php';
            NotificationHelper::notifyUser(
                $userId,
                "🏆 Achievement Unlocked!",
                "You've earned the '{$achievement['title']}' achievement and {$achievement['points']} bonus points!",
                '/ergon/profile'
            );
        }
        
        return $result;
    }
    
    public function calculateTaskPoints($task) {
        $basePoints = 10;
        
        // Priority multiplier
        $priorityMultiplier = match($task['priority']) {
            'high' => 2.0,
            'medium' => 1.5,
            'low' => 1.0,
            default => 1.0
        };
        
        // On-time bonus
        $onTimeBonus = (strtotime($task['updated_at']) <= strtotime($task['deadline'])) ? 1.5 : 1.0;
        
        // Progress bonus
        $progressBonus = $task['progress'] / 100;
        
        return round($basePoints * $priorityMultiplier * $onTimeBonus * (1 + $progressBonus));
    }
}
?>