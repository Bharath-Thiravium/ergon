<?php
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';

$db  = Database::connect();
$sql = file_get_contents(__DIR__ . '/sql/ra_bills.sql');

echo '<pre>';
foreach (array_filter(array_map('trim', explode(';', $sql)), fn($s) => strlen(preg_replace('/^--.*$/m', '', $s)) > 5) as $stmt) {
    try {
        $db->exec($stmt);
        echo '✅ ' . htmlspecialchars(substr(preg_replace('/\s+/', ' ', $stmt), 0, 80)) . "\n";
    } catch (PDOException $e) {
        echo '❌ ' . htmlspecialchars(substr(preg_replace('/\s+/', ' ', $stmt), 0, 80)) . "\n   " . htmlspecialchars($e->getMessage()) . "\n";
    }
}
echo '</pre>Done.';
