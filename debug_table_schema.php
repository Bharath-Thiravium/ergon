<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    $tables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments'];

    foreach ($tables as $t) {
        echo "=== $t ===\n";
        $rows = $db->query("SHOW CREATE TABLE $t")->fetch(PDO::FETCH_ASSOC);
        echo $rows['Create Table'] . "\n\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
