<?php
// --- Force clean output ---
if (ob_get_level()) ob_end_clean();
ob_start();

// --- Start session if not already active ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Destroy all session data ---
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// --- Disable browser caching ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- Prevent prefetch from login redirect ---
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// --- Redirect securely ---
header("Location: /ergon/login?logged_out=1");
exit;
?>