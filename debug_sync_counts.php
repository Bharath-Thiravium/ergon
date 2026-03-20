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

    $queries = [
        'quotations'      => 'SELECT quotation_number, customer_id, company_id, total_amount, quotation_date, status FROM finance_quotations',
        'purchase_orders' => 'SELECT po_number, customer_id, company_id, total_amount, po_date, status FROM finance_purchase_orders',
        'invoices'        => 'SELECT invoice_number, customer_id, company_id, total_amount, subtotal, paid_amount, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, payment_status, outstanding_amount FROM finance_invoices',
        'payments'        => 'SELECT payment_number, customer_id, company_id, amount, payment_date, reference_number, status FROM finance_payments',
    ];

    foreach ($queries as $name => $sql) {
        try {
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo "$name: " . count($rows) . " rows from PG\n";
            if (count($rows) > 0) {
                echo "  sample: " . json_encode(array_slice($rows[0], 0, 4, true)) . "\n";
            }
        } catch (Exception $e) {
            echo "$name: ERROR - " . $e->getMessage() . "\n";
        }
    }

    // Also check MySQL counts
    $db = Database::connect();
    echo "\n=== MySQL counts ===\n";
    foreach (['finance_quotations','finance_purchase_orders','finance_invoices','finance_payments'] as $t) {
        $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "$t: $count rows\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
