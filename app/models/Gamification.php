<?php
require_once __DIR__ . '/../config/database.php';

class Gamification {
    private $db;
    private $table = 'user_points';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function addPoints($userId, $points, $reason, $referenceType = 'task', $referenceId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, points, reason, reference_type, reference_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$userId, $points, $reason, $referenceType, $referenceId]);
        
        if ($result) {
            $this->checkBadges($userId);
        }
        
        return $result;
    }
    
    public function getTotalPoints($userId) {
        $stmt = $this->db->prepare("
            SELECT SUM(points) as total 
            FROM {$this->table} 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    public function getLeaderboard($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT u.name, SUM(up.points) as total_points 
            FROM {$this->table} up 
            JOIN users u ON up.user_id = u.id 
            GROUP BY up.user_id 
            ORDER BY total_points DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getUserRank($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) + 1 as rank 
            FROM (
                SELECT user_id, SUM(points) as total 
                FROM {$this->table} 
                GROUP BY user_id 
                HAVING total > (
                    SELECT SUM(points) 
                    FROM {$this->table} 
                    WHERE user_id = ?
                )
            ) as higher_users
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['rank'] ?? 1;
    }
    
    public function checkBadges($userId) {
        $stmt = $this->db->prepare("
            SELECT bd.* FROM badge_definitions bd 
            WHERE bd.is_active = 1 
            AND bd.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
        ");
        $stmt->execute([$userId]);
        $availableBadges = $stmt->fetchAll();
        
        foreach ($availableBadges as $badge) {
            if ($this->checkBadgeCriteria($userId, $badge)) {
                $this->awardBadge($userId, $badge['id']);
            }
        }
    }
    
    private function checkBadgeCriteria($userId, $badge) {
        switch ($badge['criteria_type']) {
            case 'points':
                return $this->getTotalPoints($userId) >= $badge['criteria_value'];
            case 'tasks':
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM daily_plans WHERE user_id = ? AND status = 'completed'");
                $stmt->execute([$userId]);
                return $stmt->fetchColumn() >= $badge['criteria_value'];
            case 'productivity':
                $stmt = $this->db->prepare("SELECT AVG(productivity_score) FROM daily_workflow_status WHERE user_id = ?");
                $stmt->execute([$userId]);
                return ($stmt->fetchColumn() ?? 0) >= $badge['criteria_value'];
        }
        return false;
    }
    
    private function awardBadge($userId, $badgeId) {
        $stmt = $this->db->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $badgeId]);
    }
    
    public function getUserBadges($userId) {
        $stmt = $this->db->prepare("
            SELECT bd.*, ub.awarded_on 
            FROM user_badges ub 
            JOIN badge_definitions bd ON ub.badge_id = bd.id 
            WHERE ub.user_id = ? 
            ORDER BY ub.awarded_on DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
