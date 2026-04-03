<?php
// Simple logout handler — also revokes any persistent mobile token
require_once __DIR__ . '/app/config/session.php';

// Revoke persistent token if present in Authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+([0-9a-f]{64})$/i', trim($authHeader), $m)) {
    try {
        require_once __DIR__ . '/app/config/database.php';
        require_once __DIR__ . '/app/services/TokenService.php';
        (new TokenService(Database::connect()))->revoke(strtolower($m[1]));
    } catch (Exception $e) {
        error_log('logout.php token revocation: ' . $e->getMessage());
    }
}

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Location: /ergon/login');
exit;
?>
