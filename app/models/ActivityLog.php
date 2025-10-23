<?php
require_once __DIR__ . '/../../config/database.php';

class ActivityLog {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function logActivity($userId, $activityType, $description = null, $isActive = true) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        return $stmt->execute([$userId, $activityType, $description, $ipAddress, $userAgent, $isActive]);
    }
    
    public function getITActivityReport($days = 7) {
        $stmt = $this->conn->prepare("
            SELECT u.name, u.email,
                   COUNT(CASE WHEN al.activity_type = 'system_ping' AND al.is_active = 1 THEN 1 END) as active_pings,
                   COUNT(CASE WHEN al.activity_type = 'break_start' THEN 1 END) as break_sessions,
                   MAX(al.created_at) as last_activity,
                   DATE(al.created_at) as activity_date
            FROM users u 
            LEFT JOIN user_departments ud ON u.id = ud.user_id
            LEFT JOIN departments d ON ud.department_id = d.id
            LEFT JOIN activity_logs al ON u.id = al.user_id AND DATE(al.created_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            WHERE (u.department LIKE '%IT%' OR u.department LIKE '%Information%' OR d.name LIKE '%Information%' OR u.name LIKE '%ilayaraja%')
            GROUP BY u.id, DATE(al.created_at)
            ORDER BY u.name, activity_date DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductivitySummary($days = 7) {
        $stmt = $this->conn->prepare("
            SELECT u.name,
                   ROUND(AVG(CASE WHEN al.is_active = 1 THEN 1 ELSE 0 END) * 100, 1) as productivity_score,
                   COUNT(DISTINCT DATE(al.created_at)) as active_days
            FROM users u 
            LEFT JOIN user_departments ud ON u.id = ud.user_id
            LEFT JOIN departments d ON ud.department_id = d.id
            LEFT JOIN activity_logs al ON u.id = al.user_id AND DATE(al.created_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            WHERE (u.department LIKE '%IT%' OR u.department LIKE '%Information%' OR d.name LIKE '%Information%' OR u.name LIKE '%ilayaraja%')
            GROUP BY u.id
            ORDER BY productivity_score DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecentActivity($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT al.*, u.name as user_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>