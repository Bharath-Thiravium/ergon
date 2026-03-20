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

    // Check company_id distribution in each table
    foreach (['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments'] as $t) {
        echo "=== $t ===\n";
        $rows = $pdo->query("SELECT company_id, COUNT(*) as cnt FROM $t GROUP BY company_id ORDER BY company_id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) echo "  company_id={$r['company_id']}: {$r['cnt']} records\n";
        echo "\n";
    }

    // Check if there's a company/tenant table
    echo "=== Tables with 'company' in name ===\n";
    $rows = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_name ILIKE '%company%' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $r) echo "  $r\n";
    echo "\n";

    // Check companies_company or similar
    foreach (['companies_company', 'company', 'core_company', 'accounts_company'] as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM $t LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "=== $t ===\n";
            foreach ($rows as $r) echo "  " . json_encode(array_slice($r, 0, 5, true)) . "\n";
            echo "\n";
        } catch (Exception $e) {}
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
