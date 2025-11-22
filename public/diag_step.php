<?php
// diag_step.php - stepwise, flush-friendly diagnostic to run on Hostinger
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force plain text
header('Content-Type: text/plain; charset=utf-8');

function out($s) {
    echo $s . "\n";
    if (function_exists('ob_flush')) { @ob_flush(); }
    if (function_exists('flush')) { @flush(); }
    usleep(100000); // 100ms
}

out("STEP 1: script start");
$projectRoot = realpath(__DIR__ . '/..');
out("STEP 2: projectRoot => " . ($projectRoot ?: 'UNRESOLVED'));

$files = [
    'views/layouts/dashboard.php',
    'views/finance/dashboard.php',
    'assets/css/ergon.css',
    'assets/css/ergon-overrides.css',
];

foreach ($files as $f) {
    $path = ($projectRoot ? $projectRoot . DIRECTORY_SEPARATOR . $f : $f);
    out("CHECK: $f => ");
    if (file_exists($path)) {
        out("  EXISTS: path=$path");
        out("  readable: " . (is_readable($path) ? 'yes' : 'no'));
        out("  size: " . @filesize($path) . " bytes");
        $head = @file_get_contents($path, false, null, 0, 240);
        if ($head !== false) {
            $head = preg_replace('/\s+/', ' ', $head);
            out("  head: " . trim(substr($head, 0, 180)));
        }
    } else {
        out("  MISSING (expected $path)");
    }
}

out("STEP 3: attempt write test to public folder");
$testFile = __DIR__ . '/diag_write_test.txt';
$ok = @file_put_contents($testFile, "diag write OK " . date('c'));
if ($ok) {
    out("  write OK: $testFile (" . filesize($testFile) . " bytes)");
    @unlink($testFile);
} else {
    out("  write FAILED for $testFile - check permissions");
}

out("STEP 4: check allow_url_fopen => " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF'));

out("STEP 5: small HTTP HEAD to ergon CSS (may hang if DNS blocked)");
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/assets/css/ergon.css';
$outHeaders = @get_headers($url, 1);
if ($outHeaders === false) {
    out("  HTTP fetch failed for $url");
} else {
    out("  HTTP headers: " . substr(json_encode($outHeaders), 0, 400));
}

out("STEP 6: done");
