<?php
// tools/debug_includes.php
echo "<h2>Debug: Included files & controller identity</h2>";

$target = __DIR__ . '/../app/controllers/AttendanceController.php';
echo "<b>Expected controller path:</b> {$target}<br>";

if (file_exists($target)) {
    echo "<b>File exists:</b> YES<br>";
    echo "<b>Realpath:</b> " . realpath($target) . "<br>";
    echo "<b>Filesize:</b> " . filesize($target) . " bytes<br>";
    echo "<b>Last modified:</b> " . date('Y-m-d H:i:s', filemtime($target)) . "<br>";
    echo "<b>MD5 (first 16 chars):</b> " . substr(md5_file($target),0,16) . "<br>";
} else {
    echo "<b>File exists:</b> NO (path wrong?)<br>";
}

// Include the file and display included files
require_once __DIR__ . '/../app/helpers/TimezoneHelper.php';
if (file_exists($target)) {
    require_once $target;
    echo "<h3>get_included_files()</h3><pre>";
    foreach (get_included_files() as $f) {
        echo realpath($f) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>Target controller not present — check path.</p>";
}

// Also show opcache status (if available)
if (function_exists('opcache_get_status')) {
    $op = opcache_get_status(false);
    echo "<h3>OPcache Status</h3><pre>";
    echo "enabled: " . ($op['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    if (!empty($op['scripts'])) {
        echo "Cached scripts (sample):\n";
        $count = 0;
        foreach ($op['scripts'] as $script => $meta) {
            echo basename($script) . " -> hits:{$meta['hits']} mtime:" . date('Y-m-d H:i:s',$meta['mtime']) . "\n";
            if (++$count >= 20) break;
        }
    }
    echo "</pre>";
} else {
    echo "<p>OPcache functions not available.</p>";
}

echo "<h3>Server info</h3><pre>";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . "\n";
echo "</pre>";
?>
