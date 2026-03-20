<?php
// One-time OPcache clear - DELETE AFTER RUNNING

$target = __DIR__ . '/app/services/DataSyncService.php';

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache reset\n";
} else {
    echo "⚠️ opcache_reset not available\n";
}

if (function_exists('opcache_invalidate')) {
    opcache_invalidate($target, true);
    echo "✓ File invalidated\n";
}

// Show what's actually in the file on disk
echo "\nFirst 200 chars of DataSyncService.php on disk:\n";
echo substr(file_get_contents($target), 0, 200) . "\n";

// Check if it contains the new code
$content = file_get_contents($target);
echo "\nContains 'Database::connect()': " . (strpos($content, 'Database::connect()') !== false ? 'YES ✓' : 'NO ✗') . "\n";
echo "Contains 'getMySQLConnection': " . (strpos($content, 'getMySQLConnection') !== false ? 'YES (old version!)' : 'NO ✓') . "\n";
echo "Contains 'isPostgreSQLAvailable': " . (strpos($content, 'isPostgreSQLAvailable') !== false ? 'YES ✓' : 'NO ✗') . "\n";
?>
