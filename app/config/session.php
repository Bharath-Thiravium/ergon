<?php
// Session optimization for Hostinger
if (session_status() === PHP_SESSION_NONE) {
    // Optimize session settings
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1440);
}
?>
