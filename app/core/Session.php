<?php
class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.gc_maxlifetime', '28800');

            $sessionName = 'ERGON_SID';
            session_name($sessionName);

            $existingId = $_COOKIE[$sessionName] ?? '';
            if ($existingId && preg_match('/^[a-zA-Z0-9,\-]{22,128}$/', $existingId)) {
                session_id($existingId);
            }

            session_start();

            $currentId = session_id();
            if ($currentId && (!isset($_COOKIE[$sessionName]) || $_COOKIE[$sessionName] !== $currentId)) {
                setcookie($sessionName, $currentId, [
                    'expires'  => 0,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
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
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        setcookie('ERGON_SID', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
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
