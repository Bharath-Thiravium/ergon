<?php
/**
 * Authentication Middleware
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../helpers/Security.php';

class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            // Check session timeout
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
                self::logout();
                return false;
            }
            
            // Validate role integrity periodically
            self::validateUserRole();
            
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Check JWT token
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $payload = Security::verifyJWT($token);
            
            if ($payload && isset($payload->user_id)) {
                $_SESSION['user_id'] = $payload->user_id;
                $_SESSION['role'] = $payload->role;
                $_SESSION['last_activity'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has required role
     */
    public static function hasRole($requiredRole) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? null;
        
        if (!$userRole) {
            return false;
        }
        
        // Handle array of roles
        if (is_array($requiredRole)) {
            return in_array($userRole, $requiredRole);
        }
        
        // Handle single role
        return $userRole === $requiredRole;
    }
    
    /**
     * Require authentication with optional role check
     */
    public static function requireAuth($roles = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($roles !== null && !self::hasRole($roles)) {
            http_response_code(403);
            echo "<h1>403 - Access Denied</h1><p>You don't have permission to access this resource.</p><a href='/ergon/dashboard'>Go to Dashboard</a>";
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($requiredRole) {
        self::requireAuth();
        
        if (!self::hasRole($requiredRole)) {
            http_response_code(403);
            if (self::isAjaxRequest()) {
                echo json_encode(['error' => 'Insufficient permissions']);
            } else {
                echo "<h1>403 - Access Denied</h1><p>You don't have permission to access this resource.</p><a href='/ergon/dashboard'>Go to Dashboard</a>";
            }
            exit;
        }
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Clear JWT cookie if exists
        if (isset($_COOKIE['jwt_token'])) {
            setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Clear any other auth cookies
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Validate user role against database
     */
    private static function validateUserRole() {
        if (!isset($_SESSION['role_last_check']) || (time() - $_SESSION['role_last_check']) > 300) {
            try {
                require_once __DIR__ . '/../../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                
                $stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || $user['status'] !== 'active') {
                    self::logout();
                    return false;
                }
                
                if ($user['role'] !== $_SESSION['role']) {
                    error_log("Role mismatch detected for user {$_SESSION['user_id']}: session={$_SESSION['role']}, db={$user['role']}");
                    $_SESSION['role'] = $user['role'];
                }
                
                $_SESSION['role_last_check'] = time();
            } catch (Exception $e) {
                error_log("Role validation error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Log authentication attempt
     */
    public static function logAuthAttempt($userId, $success, $ip, $userAgent) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO audit_logs (user_id, module, action, description, ip_address, user_agent) 
                VALUES (?, 'auth', ?, ?, ?, ?)
            ");
            
            $action = $success ? 'login_success' : 'login_failed';
            $description = $success ? 'User logged in successfully' : 'Failed login attempt';
            
            $stmt->execute([$userId, $action, $description, $ip, $userAgent]);
        } catch (Exception $e) {
            error_log("Failed to log auth attempt: " . $e->getMessage());
        }
    }
}
?>