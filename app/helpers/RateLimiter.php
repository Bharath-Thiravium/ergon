<?php
/**
 * Rate Limiter for Brute Force Protection
 */

class RateLimiter {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->createTable();
    }
    
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempts INT DEFAULT 1,
            last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            blocked_until TIMESTAMP NULL,
            INDEX idx_ip (ip_address),
            INDEX idx_blocked (blocked_until)
        )";
        $this->db->exec($sql);
    }
    
    public function isBlocked($ip) {
        $stmt = $this->db->prepare("SELECT blocked_until FROM login_attempts WHERE ip_address = ? AND blocked_until > NOW()");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() !== false;
    }
    
    public function recordAttempt($ip, $success = false) {
        if ($success) {
            // Clear attempts on successful login
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip]);
            return;
        }
        
        // Record failed attempt
        $stmt = $this->db->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $current = $stmt->fetchColumn();
        
        if ($current) {
            $newAttempts = $current + 1;
            $blockedUntil = $newAttempts >= 5 ? date('Y-m-d H:i:s', strtotime('+10 minutes')) : null;
            
            $stmt = $this->db->prepare("UPDATE login_attempts SET attempts = ?, blocked_until = ? WHERE ip_address = ?");
            $stmt->execute([$newAttempts, $blockedUntil, $ip]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)");
            $stmt->execute([$ip]);
        }
    }
    
    public function getRemainingAttempts($ip) {
        $stmt = $this->db->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $attempts = $stmt->fetchColumn() ?: 0;
        return max(0, 5 - $attempts);
    }
}
?>