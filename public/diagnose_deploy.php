<?php
/**
 * diagnose_deploy.php
 * Simple, read-only diagnostic you can upload to the Hostinger webroot
 * and open in a browser to gather deployed file timestamps and HTTP headers
 * for CSS and the dashboard page. It writes a `diagnose_report.txt` in
 * the same folder for easy download.
 */
header('Content-Type: text/plain; charset=utf-8');

$out = [];
$out[] = "=== Ergon Hostinger Browser Diagnostic ===";
$out[] = "Timestamp: " . date('c');
$out[] = "PHP version: " . PHP_VERSION;
$out[] = "SAPI: " . php_sapi_name();
$out[] = "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
$out[] = "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'unknown');
$out[] = "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown');

$projectRoot = realpath(__DIR__ . '/..');
$out[] = "Assumed project root (one level up from public): " . ($projectRoot ?: 'unresolved');

$checkFiles = [
    'views/layouts/dashboard.php',
    'views/finance/dashboard.php',
    'assets/css/ergon.css',
    'assets/css/ergon-overrides.css',
];

$out[] = "\n-- Files (filesystem checks) --";
foreach ($checkFiles as $rel) {
    $path = $projectRoot . DIRECTORY_SEPARATOR . $rel;
    if (file_exists($path)) {
        $out[] = "$rel: EXISTS";
        $out[] = "  path: $path";
        $out[] = "  readable: " . (is_readable($path) ? 'yes' : 'no');
        $out[] = "  size: " . filesize($path) . " bytes";
        $out[] = "  mtime: " . date('c', filemtime($path));
        $first = @file_get_contents($path, false, null, 0, 240);
        if ($first !== false) {
            $first = preg_replace("/\s+/