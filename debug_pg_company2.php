<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

$config = Database::getPostgreSQLConfig();
$pg = $config['postgresql'];

try {
    $pdo = new PDO(
        "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}",
        $pg['username'], $pg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );

    // Get all columns + data from authentication_company
    echo "=== authentication_company columns ===\n";
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='authentication_company' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "\n\n";

    echo "=== authentication_company data ===\n";
    $rows = $pdo->query("SELECT * FROM authentication_company ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "id={$r['id']}: " . json_encode(array_slice($r, 0, 8, true)) . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
