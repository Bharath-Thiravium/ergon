<?php
/**
 * Session domain diagnostic + self-contained login fix.
 * Upload this ONE file to the live server root and visit it once.
 * It will tell you exactly what is happening and fix the session.
 */

// ── 1. Show what the server is currently doing ────────────────────────────────
$info = [
    'php_version'          => PHP_VERSION,
    'session.cookie_domain'=> ini_get('session.cookie_domain'),
    'session.use_cookies'  => ini_get('session.use_cookies'),
    'HTTP_HOST'            => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'HTTPS'                => $_SERVER['HTTPS'] ?? 'off',
    'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'none',
    'DOCUMENT_ROOT'        => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'session_save_path'    => session_save_path(),
    'ERGON_SID_cookie'     => $_COOKIE['ERGON_SID'] ?? 'NOT SET',
    'PHPSESSID_cookie'     => $_COOKIE['PHPSESSID'] ?? 'NOT SET',
];

// ── 2. Try to start session with manual cookie ────────────────────────────────
ini_set('session.use_cookies',      '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid',    '0');

$sessionName = 'ERGON_SID';
session_name($sessionName);

$existingId = $_COOKIE[$sessionName] ?? '';
if ($existingId && preg_match('/^[a-zA-Z0-9,\-]{22,128}$/', $existingId)) {
    session_id($existingId);
}

session_start();

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$newId = session_id();
setcookie($sessionName, $newId, [
    'expires'  => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$_SESSION['test'] = 'session_works_' . time();

$info['new_session_id']   = $newId;
$info['session_test_val'] = $_SESSION['test'];
$info['session_writable'] = isset($_SESSION['test']) ? 'YES' : 'NO';

header('Content-Type: application/json');
echo json_encode($info, JSON_PRETTY_PRINT);
