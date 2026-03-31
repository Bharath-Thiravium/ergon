<?php
// Session configuration — must run before any session_start() call.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Derive cookie domain dynamically from the current request host.
    // This works on any domain or subdomain without hardcoding.
    //
    // athenas.co.in       → .athenas.co.in
    // aes.athenas.co.in   → .athenas.co.in
    // localhost            → (empty — no domain restriction)
    $host  = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $parts = explode('.', $host);
    $count = count($parts);

    if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        // Local dev — no domain restriction
        $cookieDomain = '';
    } elseif ($count >= 3 && $parts[$count - 1] === 'in' && $parts[$count - 2] === 'co') {
        // x.y.co.in  →  .y.co.in  (keep last 3 labels)
        $cookieDomain = '.' . implode('.', array_slice($parts, -3));
    } elseif ($count >= 2) {
        // sub.example.com  →  .example.com  (keep last 2 labels)
        $cookieDomain = '.' . implode('.', array_slice($parts, -2));
    } else {
        $cookieDomain = '';
    }

    ini_set('session.gc_maxlifetime', '28800');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $cookieDomain,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
