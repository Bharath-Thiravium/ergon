<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Security {
    private static $jwtSecret = 'ergon_jwt_secret_key_2024';
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generateJWT($userId, $role) {
        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        return JWT::encode($payload, self::$jwtSecret, 'HS256');
    }
    
    public static function verifyJWT($token) {
        try {
            return JWT::decode($token, new Key(self::$jwtSecret, 'HS256'));
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function getClientIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    public static function logAudit($userId, $module, $action, $description) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "INSERT INTO audit_logs (user_id, module, action, description, ip_address) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId, $module, $action, $description, self::getClientIP()]);
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}
?>