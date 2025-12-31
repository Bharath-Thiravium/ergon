<?php
// tools/clear_opcache.php
if (!function_exists('opcache_reset')) {
    echo "opcache_reset() not available on this host. Ask Hostinger support or restart PHP-FPM.\n";
    exit;
}

$result = @opcache_reset();
if ($result) {
    echo "OPcache reset: SUCCESS\n";
} else {
    echo "OPcache reset: FAILED (insufficient permissions?)\n";
}

// Also try clearing APCu user cache if present
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cache cleared.\n";
}
?>
