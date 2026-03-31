<?php
// Upload to live server, visit once, share the output, then delete.
echo '<pre>';
echo "PHP version: " . PHP_VERSION . "\n";
echo "session.cookie_domain (php.ini): " . ini_get('session.cookie_domain') . "\n";
echo "session.use_cookies (php.ini): " . ini_get('session.use_cookies') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'none') . "\n\n";

// Test: does session_set_cookie_params actually override php.ini?
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '.athenas.co.in',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$params = session_get_cookie_params();
echo "After session_set_cookie_params:\n";
echo "  domain: " . $params['domain'] . "\n";
echo "  secure: " . ($params['secure'] ? 'true' : 'false') . "\n\n";

// Test: start session and check what Set-Cookie header is sent
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session started successfully\n\n";

// Show all response headers being sent
$headers = headers_list();
echo "Response headers:\n";
foreach ($headers as $h) {
    echo "  " . $h . "\n";
}

// Check if session.php file exists and show its first line
$sessionFile = __DIR__ . '/app/config/session.php';
echo "\nsession.php exists: " . (file_exists($sessionFile) ? 'YES' : 'NO') . "\n";
if (file_exists($sessionFile)) {
    $lines = file($sessionFile);
    echo "session.php line 1: " . trim($lines[0]) . "\n";
    echo "session.php line 14: " . trim($lines[13] ?? 'N/A') . "\n";
}
echo '</pre>';
