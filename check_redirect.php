<?php
header('Content-Type: text/plain');

echo "=== REDIRECT DIAGNOSTIC ===\n\n";

// 1. Server & domain info
echo "--- Server Info ---\n";
echo "HTTP_HOST:       " . ($_SERVER['HTTP_HOST'] ?? 'n/a') . "\n";
echo "SERVER_NAME:     " . ($_SERVER['SERVER_NAME'] ?? 'n/a') . "\n";
echo "REQUEST_URI:     " . ($_SERVER['REQUEST_URI'] ?? 'n/a') . "\n";
echo "DOCUMENT_ROOT:   " . ($_SERVER['DOCUMENT_ROOT'] ?? 'n/a') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'n/a') . "\n";
echo "HTTPS:           " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'n/a') . "\n\n";

// 2. Check .htaccess files up the tree for redirect rules
echo "--- .htaccess Redirect Rules ---\n";
$dir = __DIR__;
$checked = [];
for ($i = 0; $i < 6; $i++) {
    $file = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $hits = array_filter($lines, fn($l) => preg_match('/Redirect|RewriteRule|RewriteCond/i', $l));
        if ($hits) {
            echo "\n[$file]\n";
            foreach ($hits as $l) echo "  " . trim($l) . "\n";
        } else {
            echo "[$file] — no redirect rules\n";
        }
        $checked[] = $file;
    }
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
}
if (!$checked) echo "No .htaccess files found\n";

// 3. Check PHP session / app-level redirects in index.php
echo "\n--- index.php Location ---\n";
$index = __DIR__ . '/index.php';
if (file_exists($index)) {
    $src = file_get_contents($index);
    preg_match_all('/(header\s*\(.*?Location.*?\)|redirect\s*\(.*?\))/i', $src, $m);
    if ($m[0]) {
        foreach ($m[0] as $h) echo "  " . trim($h) . "\n";
    } else {
        echo "  No Location headers found in index.php\n";
    }
} else {
    echo "  index.php not found\n";
}

// 4. Check app config for hardcoded base URL
echo "\n--- Config Base URL ---\n";
$configs = [
    __DIR__ . '/app/config/config.php',
    __DIR__ . '/app/config/app.php',
    __DIR__ . '/config.php',
    __DIR__ . '/app/config/database.php',
];
foreach ($configs as $f) {
    if (!file_exists($f)) continue;
    $src = file_get_contents($f);
    preg_match_all('/.*(base_url|APP_URL|siteurl|home|BASE_URL).*/i', $src, $m);
    if ($m[0]) {
        echo "\n[$f]\n";
        foreach ($m[0] as $l) echo "  " . trim($l) . "\n";
    }
}

// 5. Simulate a self-request to catch any immediate redirect
echo "\n--- Self-Request Headers (curl) ---\n";
if (function_exists('curl_init')) {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . '/ergon/';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "GET $url\n";
    echo "Response code: $code\n";
    // Print only status + location header
    foreach (explode("\n", $resp) as $line) {
        $line = trim($line);
        if (preg_match('/^(HTTP\/|Location:)/i', $line)) echo "  $line\n";
    }
} else {
    echo "curl not available\n";
}

echo "\n=== DONE ===\n";
