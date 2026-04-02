<?php
/**
 * Session persistence test
 * Step 1: https://aes.athenas.co.in/ergon/diag_session.php?action=write
 * Step 2: https://aes.athenas.co.in/ergon/diag_session.php?action=read
 * DELETE after debugging.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_GET['action'] ?? 'info';

// Apply same session config as the app
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

$cookieDomain = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($cookieDomain, ':') !== false) {
    $cookieDomain = explode(':', $cookieDomain)[0];
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $cookieDomain,
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$info = [
    'session_id'          => session_id(),
    'session_save_path'   => session_save_path(),
    'cookie_domain'       => $cookieDomain,
    'cookie_secure'       => $isHttps ? 'true' : 'false',
    'HTTPS'               => $_SERVER['HTTPS'] ?? '(empty)',
    'X_FORWARDED_PROTO'   => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(empty)',
    'cookies_received'    => $_COOKIE,
    'session_data'        => $_SESSION,
];

if ($action === 'write') {
    $_SESSION['test_value'] = 'hello_' . time();
    $_SESSION['user_id']    = 999;
    $info['action']         = 'WRITTEN: ' . $_SESSION['test_value'];
} elseif ($action === 'read') {
    $info['action'] = isset($_SESSION['user_id'])
        ? 'READ OK — user_id=' . $_SESSION['user_id'] . '  test_value=' . ($_SESSION['test_value'] ?? 'missing')
        : 'READ FAILED — session is empty (cookie not sent back or session file missing)';
}

header('Content-Type: text/plain');
echo "=== SESSION PERSISTENCE TEST ===\n\n";
foreach ($info as $k => $v) {
    if (is_array($v)) {
        echo str_pad($k, 24) . ": " . (empty($v) ? '(empty)' : json_encode($v)) . "\n";
    } else {
        echo str_pad($k, 24) . ": $v\n";
    }
}

// Also check if session file actually exists on disk
$savePath = session_save_path();
if ($savePath && is_dir($savePath)) {
    $sessionFile = $savePath . '/sess_' . session_id();
    echo "\nsession_file_exists    : " . (file_exists($sessionFile) ? 'YES — ' . $sessionFile : 'NO — file not found at ' . $sessionFile) . "\n";
    echo "session_file_writable  : " . (is_writable($savePath) ? 'YES' : 'NO — cannot write sessions!') . "\n";
}
?>
