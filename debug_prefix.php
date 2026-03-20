<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();
    $prefix = $_GET['prefix'] ?? 'TC';
    $len = strlen($prefix);

    echo "=== PREFIX: $prefix (len=$len) ===\n\n";

    $tables = [
        'finance_quotations'     => 'quotation_number',
        'finance_purchase_orders'=> 'po_number',
        'finance_invoices'       => 'invoice_number',
        'finance_payments'       => 'payment_id',
        'finance_customers'      => 'customer_id',
    ];

    foreach ($tables as $table => $col) {
        $total = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $matched = $db->prepare("SELECT COUNT(*) FROM $table WHERE LEFT($col, $len) = ?");
        $matched->execute([$prefix]);
        $count = $matched->fetchColumn();

        $sample = $db->query("SELECT $col FROM $table LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
        echo "$table: total=$total, matched=$count\n";
        echo "  samples: " . implode(', ', $sample) . "\n\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
