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
    echo "Connected to PostgreSQL OK\n\n";

    $tables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customer'];

    foreach ($tables as $table) {
        echo "=== $table ===\n";
        try {
            // Get columns
            $cols = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "  {$col['column_name']} ({$col['data_type']})\n";
            }
            // Get count + 1 sample row
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "  COUNT: $count\n";
            if ($count > 0) {
                $row = $pdo->query("SELECT * FROM $table LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                echo "  SAMPLE: " . json_encode(array_slice($row, 0, 6, true)) . "\n";
            }
        } catch (Exception $e) {
            echo "  ERROR: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
