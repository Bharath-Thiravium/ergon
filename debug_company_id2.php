<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    // Show finance_companies
    echo "=== finance_companies ===\n";
    $rows = $db->query("SELECT company_id, company_prefix, company_name FROM finance_companies ORDER BY company_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) echo "  id={$r['company_id']} prefix={$r['company_prefix']} name={$r['company_name']}\n";
    echo "\n";

    // Show company_id distribution per table
    $tables = [
        'finance_quotations'      => 'company_id',
        'finance_purchase_orders' => 'company_id',
        'finance_invoices'        => 'company_id',
        'finance_payments'        => 'company_id',
    ];

    foreach ($tables as $table => $col) {
        echo "=== $table ===\n";
        $rows = $db->query("SELECT fc.company_prefix, t.company_id, COUNT(*) as cnt
            FROM $table t
            LEFT JOIN finance_companies fc ON fc.company_id = t.company_id
            GROUP BY t.company_id, fc.company_prefix ORDER BY t.company_id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $prefix = $r['company_prefix'] ?? '(null)';
            echo "  company_id={$r['company_id']} prefix=$prefix: {$r['cnt']} records\n";
        }
        echo "\n";
    }

    // Test API resolution for TC
    echo "=== resolveCompanyId test ===\n";
    require_once __DIR__ . '/src/api/dashboard/prefix-resolver.php';
    foreach (['TC','BKC','AS','SE','BKGE','PGEL'] as $p) {
        $id = resolveCompanyId($p, $db);
        echo "  $p => company_id=" . ($id ?? 'null') . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
