<?php
require_once __DIR__ . '/../config/database.php';

class Gamification {
    private $db;
    private $table = 'user_points';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function addPoints($userId, $points, $reason) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, points, reason) 
            VALUES (?, ?, ?)
        ");
        $result = $stmt->execute([$userId, $points, $reason]);
        
        if ($result) {
            $this->updateTotalPoints($userId);
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
    
    private function updateTotalPoints($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET total_points = (
                SELECT SUM(points) 
                FROM {$this->table} 
                WHERE user_id = ?
            ) 
            WHERE id = ?
        ");
        return $stmt->execute([$userId, $userId]);
    }
}
?>