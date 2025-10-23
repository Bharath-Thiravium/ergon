<?php
class RoleMiddleware {
    public static function requireRole($allowed_roles) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $user_role = $_SESSION['role'] ?? 'user';
        
        if (!in_array($user_role, (array)$allowed_roles)) {
            http_response_code(403);
            die('Access Denied - Insufficient permissions');
        }
    }
    
    public static function isOwner() {
        return ($_SESSION['role'] ?? '') === 'owner';
    }
    
    public static function isAdmin() {
        return in_array($_SESSION['role'] ?? '', ['owner', 'admin']);
    }
    
    public static function isUser() {
        return isset($_SESSION['user_id']);
    }
}