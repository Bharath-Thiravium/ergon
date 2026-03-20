<?php
class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Only use custom session path in development
            if (strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'laragon') !== false) {
                $sessionPath = __DIR__ . '/../../storage/sessions';
                if (!is_dir($sessionPath)) {
                    @mkdir($sessionPath, 0755, true);
                }
                if (is_writable($sessionPath)) {
                    session_save_path($sessionPath);
                }
            }
            session_start();
        }
        if (!headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }
    
    public static function destroy() {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getUser() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
}
?>
