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

$pg = new PDO(
    "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;connect_timeout=10",
    'postgres', $_ENV['SAP_PG_PASS'] ?? 'mango',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]
);
echo "PG connected\n"; flush();

$queries = [
    'companies'       => "SELECT id, company_prefix, name FROM authentication_company WHERE approval_status='approved'",
    'customers'       => "SELECT id, name, gstin FROM finance_customer WHERE is_active=true",
    'quotations'      => "SELECT id, quotation_number, customer_id, company_id, total_amount, quotation_date, status FROM finance_quotations",
    'purchase_orders' => "SELECT id, po_number, customer_id, company_id, total_amount, po_date, status FROM finance_purchase_orders",
    'invoices'        => "SELECT id, invoice_number, customer_id, company_id, total_amount, subtotal, paid_amount, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, payment_status, outstanding_amount FROM finance_invoices",
    'payments'        => "SELECT id, payment_number, customer_id, company_id, amount, payment_date, COALESCE(reference_number, payment_number) as reference_number, status FROM finance_payments",
];

foreach ($queries as $name => $sql) {
    echo "Fetching $name... "; flush();
    $stmt = $pg->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    echo count($rows) . " rows OK\n"; flush();
}

echo "All fetched. Now testing MySQL insert for purchase_orders...\n"; flush();

// Load MySQL
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();
echo "MySQL connected\n"; flush();

// Re-fetch just purchase_orders
$pg2 = new PDO(
    "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;connect_timeout=10",
    'postgres', $_ENV['SAP_PG_PASS'] ?? 'mango',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$stmt = $pg2->prepare("SELECT id, po_number, customer_id, company_id, total_amount, po_date, status FROM finance_purchase_orders");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
unset($pg2);
echo "PG fetch done (" . count($rows) . " rows), PG closed\n"; flush();

$insertSql = 'INSERT INTO finance_purchase_orders (id, po_number, customer_id, company_id, po_total_value, po_date, po_status)
              VALUES (?, ?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE po_number=VALUES(po_number), company_id=VALUES(company_id),
              po_total_value=VALUES(po_total_value), po_date=VALUES(po_date), po_status=VALUES(po_status)';
$ins = $db->prepare($insertSql);
foreach ($rows as $i => $row) {
    $ins->execute([$row['id'], $row['po_number'], $row['customer_id'], $row['company_id'], $row['total_amount'], $row['po_date'], $row['status']]);
    echo "Inserted row $i (id={$row['id']})\n"; flush();
}
echo "Done\n";
