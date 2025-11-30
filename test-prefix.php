<?php
require 'app/config/database.php';

$db = Database::connect();

$inv = $db->query('SELECT DISTINCT SUBSTRING(invoice_number, 1, 4) as prefix FROM finance_invoices LIMIT 5')->fetchAll(PDO::FETCH_COLUMN);
echo "Invoice prefixes: " . implode(', ', $inv) . "\n";

$po = $db->query('SELECT DISTINCT SUBSTRING(po_number, 1, 4) as prefix FROM finance_purchase_orders LIMIT 5')->fetchAll(PDO::FETCH_COLUMN);
echo "PO prefixes: " . implode(', ', $po) . "\n";

// Test with BKGE prefix
echo "\n=== Testing with BKGE prefix ===\n";
$inv_bkge = $db->query("SELECT COUNT(*) as cnt, SUM(total_amount - COALESCE(paid_amount, 0)) as inflow FROM finance_invoices WHERE invoice_number LIKE 'BKGE%' AND (total_amount - COALESCE(paid_amount, 0)) > 0")->fetch(PDO::FETCH_ASSOC);
echo "BKGE Invoices: " . json_encode($inv_bkge) . "\n";

$po_bkge = $db->query("SELECT COUNT(*) as cnt, SUM(total_amount) as commitments FROM finance_purchase_orders WHERE po_number LIKE 'BKGE%' AND status IN ('ACTIVE', 'RELEASED', 'Active', 'Released', 'draft')")->fetch(PDO::FETCH_ASSOC);
echo "BKGE POs: " . json_encode($po_bkge) . "\n";
