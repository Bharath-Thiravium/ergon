<?php
// Clear OPcache and force JS re-serve
opcache_reset();

$jsFiles = [
    __DIR__ . '/views/finance/dashboard-loader.js',
    __DIR__ . '/views/finance/dashboard-svg-charts.js',
];

echo "<pre>";
foreach ($jsFiles as $f) {
    echo file_exists($f) ? "OK: $f\n" : "MISSING: $f\n";
}

echo "\nOPcache reset: done\n";
echo "Upload dashboard-loader.js and dashboard.php then visit this page.\n";
echo "</pre>";
