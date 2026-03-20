<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    // Show distinct company_id values per table with samples
    $tables = [
        'finance_quotations'      => ['company_id', 'quotation_number'],
        'finance_purchase_orders' => ['company_id', 'po_number'],
        'finance_invoices'        => ['company_id', 'invoice_number'],
        'finance_payments'        => ['company_id', 'payment_number'],
    ];

    foreach ($tables as $table => [$cid, $doc]) {
        echo "=== $table ===\n";
        $rows = $db->query("SELECT $cid, COUNT(*) as cnt, MIN($doc) as sample FROM $table GROUP BY $cid ORDER BY $cid")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            echo "  company_id={$r[$cid]}: {$r['cnt']} records, sample={$r['sample']}\n";
        }
        echo "\n";
    }

    // Also show what company_id maps to in customers
    echo "=== finance_customers (id → customer_code/name) ===\n";
    $rows = $db->query("SELECT id, customer_name FROM finance_customers ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "  id={$r['id']}: {$r['customer_name']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
