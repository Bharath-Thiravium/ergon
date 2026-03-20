<?php
ini_set('max_execution_time', 60);
header('Content-Type: text/plain');
if (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

$envFile = file_exists(__DIR__ . '/.env.production') ? __DIR__ . '/.env.production' : __DIR__ . '/.env';
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') !== false && $line[0] !== '#') {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

echo "Connecting to PostgreSQL...\n"; flush();
try {
    $pg = new PDO(
        "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;connect_timeout=10",
        'postgres',
        $_ENV['SAP_PG_PASS'] ?? 'mango',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]
    );
    echo "Connected OK\n"; flush();
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n"; exit;
}

$tables = [
    'companies'       => "SELECT COUNT(*) FROM authentication_company WHERE approval_status='approved'",
    'customers'       => "SELECT COUNT(*) FROM finance_customer WHERE is_active=true",
    'quotations'      => "SELECT COUNT(*) FROM finance_quotations",
    'purchase_orders' => "SELECT COUNT(*) FROM finance_purchase_orders",
    'invoices'        => "SELECT COUNT(*) FROM finance_invoices",
    'payments'        => "SELECT COUNT(*) FROM finance_payments",
];

foreach ($tables as $name => $sql) {
    echo "Counting $name... "; flush();
    try {
        $count = $pg->query($sql)->fetchColumn();
        echo "$count rows\n"; flush();
    } catch (Exception $e) {
        echo "ERR: " . $e->getMessage() . "\n"; flush();
    }
}

echo "Done\n";
