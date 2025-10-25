<?php
/**
 * Security Audit Logger
 */

class AuditLogger {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->createTable();
    }
    
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            event_type VARCHAR(50) NOT NULL,
            event_description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            request_uri VARCHAR(500),
            additional_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_event (event_type),
            INDEX idx_ip (ip_address),
            INDEX idx_created (created_at)
        )";
        $this->db->exec($sql);
    }
    
    public function log($eventType, $description, $userId = null, $additionalData = []) {
        $stmt = $this->db->prepare("
            INSERT INTO security_logs (user_id, event_type, event_description, ip_address, user_agent, request_uri, additional_data)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $eventType,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            json_encode($additionalData)
        ]);
    }
    
    // Convenience methods for common events
    public static function logLogin($userId, $success = true) {
        $logger = new self();
        $logger->log(
            $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            $success ? 'User logged in successfully' : 'Failed login attempt',
            $success ? $userId : null,
            ['success' => $success, 'user_id' => $userId]
        );
    }
    
    public static function logLogout($userId) {
        $logger = new self();
        $logger->log('LOGOUT', 'User logged out', $userId);
    }
    
    public static function logPasswordReset($userId) {
        $logger = new self();
        $logger->log('PASSWORD_RESET', 'Password reset performed', $userId);
    }
    
    public static function logAdminAction($userId, $action, $targetId = null) {
        $logger = new self();
        $logger->log('ADMIN_ACTION', $action, $userId, ['target_id' => $targetId]);
    }
    
    public static function logSecurityEvent($eventType, $description, $additionalData = []) {
        $logger = new self();
        $logger->log($eventType, $description, $_SESSION['user_id'] ?? null, $additionalData);
    }
    
    public function getRecentLogs($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT sl.*, u.name as user_name 
            FROM security_logs sl 
            LEFT JOIN users u ON sl.user_id = u.id 
            ORDER BY sl.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>