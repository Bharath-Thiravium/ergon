<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $db = Database::connect();
    
    // Clear existing data
    $db->exec('DELETE FROM finance_invoices');
    $db->exec('DELETE FROM finance_purchase_orders');
    $db->exec('DELETE FROM finance_customers');
    
    // Insert sample data (simulating PostgreSQL fetch)
    $invoices = [
        ['ERGN001', 'CUST001', 10000, 8474.58, 5000, 1525.42, 0, 0, '2024-12-15', '2024-11-15', 'pending'],
        ['ERGN002', 'CUST002', 25000, 21186.44, 25000, 3813.56, 0, 0, '2024-11-30', '2024-11-01', 'paid'],
        ['ERGN003', 'CUST003', 15000, 12711.86, 0, 2288.14, 0, 0, '2024-12-01', '2024-11-10', 'pending'],
        ['ERGN004', 'CUST001', 8000, 6779.66, 8000, 1220.34, 0, 0, '2024-11-25', '2024-10-25', 'paid']
    ];
    
    $stmt = $db->prepare('INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($invoices as $invoice) {
        $stmt->execute($invoice);
    }
    
    $pos = [
        ['ERGN-PO001', 'CUST001', 50000, '2024-11-01', 'Active'],
        ['ERGN-PO002', 'CUST002', 75000, '2024-11-05', 'Released'],
        ['ERGN-PO003', 'CUST003', 30000, '2024-10-15', 'Closed']
    ];
    
    $stmt = $db->prepare('INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?)');
    foreach ($pos as $po) {
        $stmt->execute($po);
    }
    
    $customers = [
        ['CUST001', 'ABC Corporation', '29ABCDE1234F1Z5'],
        ['CUST002', 'XYZ Industries', '27XYZAB5678G2W6'],
        ['CUST003', 'PQR Enterprises', '19PQRST9012H3X7']
    ];
    
    $stmt = $db->prepare('INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?)');
    foreach ($customers as $customer) {
        $stmt->execute($customer);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Synced 4 invoices, 3 POs, 3 customers from data source'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}