<?php
// Session configuration — must run before any session_start() call.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Disable PHP's automatic session cookie — we send it manually below
    // so we have full control over the domain attribute.
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies', '0');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', '28800');
    ini_set('session.use_strict_mode', '1');

    // Resume existing session from cookie if present, otherwise start fresh
    $sessionName = 'ERGON_SID';
    session_name($sessionName);

    $existingId = $_COOKIE[$sessionName] ?? '';
    if ($existingId && preg_match('/^[a-zA-Z0-9,\-]{22,128}$/', $existingId)) {
        session_id($existingId);
    }

    session_start();

    // Send the session cookie manually with no domain — browser scopes it
    // to the exact request host (aes.athenas.co.in), never rejected.
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
?>
