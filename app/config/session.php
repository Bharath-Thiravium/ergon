<?php
/**
 * Centralized session bootstrap.
 *
 * Every file that is hit directly (api/*, public/api/*, etc.) must
 * require_once this file instead of calling session_start() itself.
 * index.php also requires this file so cookie params are set in one place.
 *
 * Safe to require_once multiple times — session_start() is only called
 * when the session is not already active.
 */

if (session_status() !== PHP_SESSION_NONE) {
    // Session already started (e.g. by index.php). Nothing to do.
    return;
}

$_sess_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])   && $_SERVER['HTTP_X_FORWARDED_SSL']   === 'on')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

// Derive the bare hostname (strip port if present)
$_sess_host = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($_sess_host, ':') !== false) {
    $_sess_host = explode(':', $_sess_host)[0];
}

// Use the leading-dot form so the cookie is valid for all sub-domains
// (e.g. both athenas.co.in and www.athenas.co.in).
// For localhost / IP addresses a leading dot is harmless.
$_sess_domain = (strpos($_sess_host, '.') !== false && !filter_var($_sess_host, FILTER_VALIDATE_IP))
    ? '.' . ltrim($_sess_host, '.')   // e.g. .athenas.co.in
    : $_sess_host;                    // localhost or bare IP — no dot prefix

ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime',  28800);  // 8 hours

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_sess_domain,
    'secure'   => $_sess_https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

unset($_sess_https, $_sess_host, $_sess_domain);
