<?php
// Ensure consistent IST timezone across all entry points (main app + standalone API files)
date_default_timezone_set('Asia/Kolkata');

// Session optimization for Hostinger
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS correctly behind Hostinger/proxy (HTTPS may not be set directly)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 28800);  // 8 hours — MUST match Controller::requireAuth() timeout

    // Prevent session locking during concurrent AJAX requests by releasing lock after read
    // (only for PHP 7.0+, safely ignored on older versions)
    if (PHP_VERSION_ID >= 70000) {
        ini_set('session.read_and_close', 1);  // Close session after read to allow parallel requests
    }
}
?>
