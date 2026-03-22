<?php
$envFile = __DIR__ . '/.env.production.bkg';
if (!file_exists($envFile)) die("No .env.production.bkg found\n");

foreach (file($envFile) as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v);
}

$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
    $_ENV['DB_USER'], $_ENV['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = file_get_contents(__DIR__ . '/sql/site_report_schema.sql');

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    $pdo->exec($stmt);
    if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $stmt, $m)) {
        echo "OK: {$m[1]}\n";
    }
}

echo "Done.\n";
