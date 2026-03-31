<?php
// Session configuration — must run before any session_start() call.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Derive the correct cookie domain from the current request host.
    // athenas.co.in     → .athenas.co.in
    // aes.athenas.co.in → .athenas.co.in
    // localhost         → '' (no restriction)
    $host  = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $parts = explode('.', $host);
    $count = count($parts);

    if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        $cookieDomain = '';
    } elseif ($count >= 3 && $parts[$count - 1] === 'in' && $parts[$count - 2] === 'co') {
        $cookieDomain = '.' . implode('.', array_slice($parts, -3)); // .athenas.co.in
    } elseif ($count >= 2) {
        $cookieDomain = '.' . implode('.', array_slice($parts, -2)); // .example.com
    } else {
        $cookieDomain = '';
    }

    // ── Purge stale cookies that were set with the wrong domain ──────────────
    // The browser may have an old PHPSESSID with domain=athenas.co.in (no dot)
    // sitting alongside the correct .athenas.co.in one. Expire both the bare
    // domain and the exact host variants so only our canonical cookie survives.
    if ($cookieDomain !== '') {
        $bareHost = ltrim($cookieDomain, '.'); // athenas.co.in
        foreach (['PHPSESSID', session_name()] as $cookieName) {
            // Expire the no-dot variant (the bad one)
            setcookie($cookieName, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => $bareHost,
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            // Expire the exact-host variant (e.g. aes.athenas.co.in)
            setcookie($cookieName, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => $host,
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

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
