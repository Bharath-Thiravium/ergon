<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

$file = __DIR__ . '/src/services/AllStatCardsService.php';

// Force OPcache invalidation
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($file, true);
    echo "OPcache invalidated for AllStatCardsService.php\n";
}
opcache_reset();
echo "OPcache reset done\n\n";

// Show which version is loaded by checking file content
$content = file_get_contents($file);
if (strpos($content, 'resolveCompanyId') !== false) {
    echo "File version: NEW (uses resolveCompanyId)\n";
} elseif (strpos($content, 'LEFT(invoice_number') !== false) {
    echo "File version: OLD (uses LEFT string matching) - NEEDS UPLOAD\n";
} else {
    echo "File version: UNKNOWN\n";
}

echo "\n=== Direct test with company_id=13 (TC) ===\n";
require_once $file;
$db = Database::connect();
$service = new AllStatCardsService($db);
$stats = $service->getAllStats('TC');
foreach ($stats as $card => $data) {
    echo "$card: " . json_encode($data) . "\n";
}
