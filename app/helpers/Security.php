<?php
class Security {
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Hostinger-specific session handling
        if (self::isHostinger()) {
            // Force session write and restart for Hostinger
            session_write_close();
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // Force session write for Hostinger
            if (self::isHostinger()) {
                session_write_close();
                session_start();
            }
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Hostinger-specific session handling
        if (self::isHostinger()) {
            session_write_close();
            session_start();
        }
        
        $isValid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
        
        // Log for debugging on Hostinger
        if (self::isHostinger()) {
            error_log('CSRF validation on Hostinger - Token exists: ' . (isset($_SESSION['csrf_token']) ? 'yes' : 'no') . ', Valid: ' . ($isValid ? 'yes' : 'no'));
        }
        
        return $isValid;
    }
    
    public static function sanitizeString($input, $maxLength = 255) {
        $clean = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return substr(trim($clean), 0, $maxLength);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function escape($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function validateInt($value, $min = null, $max = null) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false) return false;
        if ($min !== null && $int < $min) return false;
        if ($max !== null && $int > $max) return false;
        return $int;
    }
    
    public static function validateGPSCoordinate($lat, $lng) {
        $lat = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($lng, FILTER_VALIDATE_FLOAT);
        
        if ($lat === false || $lng === false) return false;
        if ($lat < -90 || $lat > 90) return false;
        if ($lng < -180 || $lng > 180) return false;
        
        return ['lat' => $lat, 'lng' => $lng];
    }
    
    public static function isHostinger() {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        
        return strpos($docRoot, '/home/') === 0 || 
               strpos($serverName, 'hostinger') !== false ||
               strpos($docRoot, '/public_html/') !== false;
    }
}
?>
