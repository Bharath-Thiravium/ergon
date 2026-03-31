<?php
// Session configuration — must run before any session_start() call.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Derive the root domain so the cookie is shared across all subdomains.
    // e.g. athenas.co.in  →  .athenas.co.in
    //      aes.athenas.co.in  →  .athenas.co.in
    // Falls back to the bare host for localhost / IP addresses.
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = strtolower(preg_replace('/:\d+$/', '', $host)); // strip port
    $parts = explode('.', $host);
    // Use the last two labels as the root domain when there are at least 3 labels
    // (handles  sub.example.com  and  sub.example.co.in  alike).
    $cookieDomain = (count($parts) >= 3)
        ? '.' . implode('.', array_slice($parts, -2))
        : $host;

    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 28800);

    session_set_cookie_params([
        'lifetime' => 0,          // session cookie — expires when browser closes
        'path'     => '/',        // accessible under every path on the domain
        'domain'   => $cookieDomain,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
?>
