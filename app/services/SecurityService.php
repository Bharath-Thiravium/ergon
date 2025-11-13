<?php
require_once __DIR__ . '/../config/database.php';

class SecurityService {
    private $conn;
    private $maxAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes in seconds
    private $rateLimitWindow = 300; // 5 minutes in seconds
    private $maxRequestsPerWindow = 10;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    public function checkRateLimit($identifier, $action = 'login') {
        try {
            $windowStart = date('Y-m-d H:i:s', time() - $this->rateLimitWindow);
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM rate_limit_log 
                WHERE identifier = ? AND action = ? AND attempted_at > ?
            ");
            $stmt->execute([$identifier, $action, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['attempts'] < $this->maxRequestsPerWindow;
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    public function logAttempt($identifier, $action = 'login', $success = false) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limit_log (identifier, action, attempted_at, success, ip_address) 
                VALUES (?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $identifier, 
                $action, 
                $success ? 1 : 0, 
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            error_log("Log attempt error: " . $e->getMessage());
        }
    }
    
    public function checkAccountLockout($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['locked' => false];
            }
            
            // Check if account is currently locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return [
                    'locked' => true,
                    'unlock_time' => $user['locked_until'],
                    'message' => 'Account is locked until ' . date('M d, Y H:i', strtotime($user['locked_until']))
                ];
            }
            
            // Check recent failed attempts
            $windowStart = date('Y-m-d H:i:s', time() - 3600); // 1 hour window
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as failed_attempts 
                FROM login_attempts 
                WHERE email = ? AND success = 0 AND attempted_at > ?
            ");
            $stmt->execute([$email, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'locked' => false,
                'failed_attempts' => $result['failed_attempts'],
                'remaining_attempts' => max(0, $this->maxAttempts - $result['failed_attempts'])
            ];
        } catch (Exception $e) {
            error_log("Account lockout check error: " . $e->getMessage());
            return ['locked' => false];
        }
    }
    
    public function recordLoginAttempt($email, $success = false) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO login_attempts (email, success, attempted_at, ip_address, user_agent) 
                VALUES (?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $email,
                $success ? 1 : 0,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            if (!$success) {
                $this->checkAndLockAccount($email);
            } else {
                $this->clearFailedAttempts($email);
            }
        } catch (Exception $e) {
            error_log("Record login attempt error: " . $e->getMessage());
        }
    }
    
    private function checkAndLockAccount($email) {
        try {
            $windowStart = date('Y-m-d H:i:s', time() - 3600);
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as failed_attempts 
                FROM login_attempts 
                WHERE email = ? AND success = 0 AND attempted_at > ?
            ");
            $stmt->execute([$email, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['failed_attempts'] >= $this->maxAttempts) {
                $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
                
                $stmt = $this->conn->prepare("
                    UPDATE users 
                    SET locked_until = ?, failed_attempts = ? 
                    WHERE email = ?
                ");
                $stmt->execute([$lockUntil, $result['failed_attempts'], $email]);
                
                // Send security alert email
                $user = $this->getUserByEmail($email);
                if ($user) {
                    require_once __DIR__ . '/EmailService.php';
                    $emailService = new EmailService();
                    $emailService->sendAccountLockedEmail(
                        $email, 
                        $user['name'], 
                        date('M d, Y H:i', strtotime($lockUntil))
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Check and lock account error: " . $e->getMessage());
        }
    }
    
    private function clearFailedAttempts($email) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET locked_until = NULL, failed_attempts = 0 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
        } catch (Exception $e) {
            error_log("Clear failed attempts error: " . $e->getMessage());
        }
    }
    
    private function getUserByEmail($email) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, locked_until, failed_attempts 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupOldLogs() {
        try {
            $cutoff = date('Y-m-d H:i:s', time() - (7 * 24 * 3600)); // 7 days
            
            $stmt = $this->conn->prepare("DELETE FROM rate_limit_log WHERE attempted_at < ?");
            $stmt->execute([$cutoff]);
            
            $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
            $stmt->execute([$cutoff]);
        } catch (Exception $e) {
            error_log("Cleanup old logs error: " . $e->getMessage());
        }
    }
}
?>