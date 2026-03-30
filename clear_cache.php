<?php
// Clear PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared<br>";
}

// Clear any session cache
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
echo "✅ Session cleared<br>";

// Force reload by touching the controller file
touch(__DIR__ . '/app/controllers/AttendanceController.php');
echo "✅ Controller touched<br>";

echo "<br><strong>✅ Cache cleared! Refresh attendance page now.</strong>";
?>
