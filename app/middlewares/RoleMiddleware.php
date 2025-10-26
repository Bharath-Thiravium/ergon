<?php
require_once __DIR__ . '/../helpers/RoleManager.php';

class RoleMiddleware {
    
    public static function check($requiredRole) {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Location: /ergon/login');
            exit;
        }
        
        $userRole = $_SESSION['role'] ?? 'user';
        
        if (!RoleManager::isHigherRole($userRole, $requiredRole) && $userRole !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
        
        return true;
    }
    
    public static function requirePermission($permission) {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Location: /ergon/login');
            exit;
        }
        
        $userRole = $_SESSION['role'] ?? 'user';
        
        if (!RoleManager::hasPermission($userRole, $permission)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        return true;
    }
    
    public static function canAccess($resource, $action = 'view') {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? 'user';
        return RoleManager::canAccess($userRole, $resource, $action);
    }
}
?>
