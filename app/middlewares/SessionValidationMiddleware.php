<?php

class SessionValidationMiddleware {
    
    public static function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Skip validation for login/logout pages
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentPath, '/login') !== false || strpos($currentPath, '/logout') !== false) {
            return;
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check if user still exists and is active
            $stmt = $db->prepare("SELECT id, status, role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Force logout if user doesn't exist or is inactive
            if (!$user || $user['status'] !== 'active') {
                self::forceLogout('Your account has been deactivated or removed.');
                return;
            }
            
            // Update session role if it changed
            if ($user['role'] !== $_SESSION['role']) {
                $_SESSION['role'] = $user['role'];
            }
            
        } catch (Exception $e) {
            error_log('Session validation error: ' . $e->getMessage());
        }
    }
    
    private static function forceLogout($message = 'Session expired') {
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session for message
        session_start();
        $_SESSION['logout_message'] = $message;
        
        // Redirect to login
        header('Location: /ergon/login');
        exit;
    }
}
?>