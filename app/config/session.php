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

    // Public second-level TLDs that need 3 labels for the root domain
    // e.g. aes.athenas.co.in → .athenas.co.in  (not .co.in)
    $multiPartTlds = ['co.in','com.au','co.uk','co.nz','co.za','com.br','co.jp','org.uk','net.au'];
    $lastTwo = implode('.', array_slice($parts, -2));
    if (count($parts) >= 4 || (count($parts) === 3 && in_array($lastTwo, $multiPartTlds))) {
        // sub.example.co.in  → .example.co.in  (last 3 labels)
        $cookieDomain = '.' . implode('.', array_slice($parts, -3));
    } elseif (count($parts) >= 3) {
        // sub.example.com  → .example.com  (last 2 labels)
        $cookieDomain = '.' . implode('.', array_slice($parts, -2));
    } else {
        // localhost or bare IP
        $cookieDomain = $host;
    }

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
