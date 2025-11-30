<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // MySQL connection
    $mysql = Database::connect();
    
    // PostgreSQL connection with SSL and timeout
    $pgDsn = "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;sslmode=require";
    $pgOptions = [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pg = new PDO($pgDsn, 'postgres', 'mango', $pgOptions);
    
    // Test PostgreSQL connection
    $testQuery = $pg->query("SELECT 1 as test");
    if (!$testQuery) {
        throw new Exception('PostgreSQL connection test failed');
    }
    
    // Clear existing MySQL data
    $mysql->exec('TRUNCATE TABLE finance_invoices');
    $mysql->exec('TRUNCATE TABLE finance_purchase_orders');
    $mysql->exec('TRUNCATE TABLE finance_customers');
    $mysql->exec('TRUNCATE TABLE finance_payments');
    $mysql->exec('TRUNCATE TABLE finance_quotations');
    
    $invoiceCount = $poCount = $customerCount = 0;
    
    $paymentCount = $quotationCount = 0;
    
    // Sync invoices
    $pgStmt = $pg->query("SELECT invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status FROM finance_invoices LIMIT 1000");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    while ($row = $pgStmt->fetch()) {
        $mysqlStmt->execute(array_values($row));
        $invoiceCount++;
    }
    
    // Sync purchase orders
    $pgStmt = $pg->query("SELECT id as po_number, customer_id, total_amount as po_total_value, created_at as po_date, status as po_status FROM finance_purchase_orders LIMIT 1000");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?)");
    while ($row = $pgStmt->fetch()) {
        $mysqlStmt->execute(array_values($row));
        $poCount++;
    }
    
    // Sync customers
    $pgStmt = $pg->query("SELECT customer_id, customer_name, gstin as customer_gstin FROM finance_customer LIMIT 1000");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?)");
    while ($row = $pgStmt->fetch()) {
        $mysqlStmt->execute(array_values($row));
        $customerCount++;
    }
    
    // Sync payments
    $pgStmt = $pg->query("SELECT payment_id, customer_id, amount, payment_date, receipt_number, status FROM finance_payments LIMIT 1000");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_payments (payment_id, customer_id, amount, payment_date, receipt_number, status) VALUES (?, ?, ?, ?, ?, ?)");
    while ($row = $pgStmt->fetch()) {
        $mysqlStmt->execute(array_values($row));
        $paymentCount++;
    }
    
    // Sync quotations
    $pgStmt = $pg->query("SELECT quotation_number, customer_id, quotation_amount, quotation_date, status FROM finance_quotations LIMIT 1000");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_quotations (quotation_number, customer_id, quotation_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?)");
    while ($row = $pgStmt->fetch()) {
        $mysqlStmt->execute(array_values($row));
        $quotationCount++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Synced {$invoiceCount} invoices, {$poCount} POs, {$customerCount} customers, {$paymentCount} payments, {$quotationCount} quotations from PostgreSQL"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PostgreSQL sync error: ' . $e->getMessage()
    ]);
}