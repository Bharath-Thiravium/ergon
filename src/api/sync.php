<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // MySQL connection
    $mysql = Database::connect();
    
    // PostgreSQL connection
    $pgHost = '72.60.218.167';
    $pgPort = '5432';
    $pgDatabase = 'modernsap';
    $pgUser = 'postgres';
    $pgPassword = 'mango';
    
    $pgDsn = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDatabase}";
    $pg = new PDO($pgDsn, $pgUser, $pgPassword);
    
    // Clear existing MySQL data
    $mysql->exec('DELETE FROM finance_invoices');
    $mysql->exec('DELETE FROM finance_purchase_orders');
    $mysql->exec('DELETE FROM finance_customers');
    
    // Sync invoices from PostgreSQL
    $pgStmt = $pg->query("SELECT invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status FROM invoices");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $invoiceCount = 0;
    while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
        $mysqlStmt->execute(array_values($row));
        $invoiceCount++;
    }
    
    // Sync purchase orders from PostgreSQL
    $pgStmt = $pg->query("SELECT po_number, customer_id, po_total_value, po_date, po_status FROM purchase_orders");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?)");
    
    $poCount = 0;
    while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
        $mysqlStmt->execute(array_values($row));
        $poCount++;
    }
    
    // Sync customers from PostgreSQL
    $pgStmt = $pg->query("SELECT customer_id, customer_name, customer_gstin FROM customers");
    $mysqlStmt = $mysql->prepare("INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?)");
    
    $customerCount = 0;
    while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
        $mysqlStmt->execute(array_values($row));
        $customerCount++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Synced {$invoiceCount} invoices, {$poCount} POs, {$customerCount} customers from PostgreSQL"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PostgreSQL sync error: ' . $e->getMessage()
    ]);
}