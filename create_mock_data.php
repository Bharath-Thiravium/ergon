<?php
/**
 * Create Mock Finance Data
 * Use this when PostgreSQL sync is not available
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $mysql = Database::connect();
    
    echo "Creating mock finance tables and data...\n\n";
    
    // Create finance_customers table
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_customers (
        customer_id VARCHAR(64) PRIMARY KEY,
        customer_name VARCHAR(255),
        customer_gstin VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create finance_invoices table
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_invoices (
        invoice_number VARCHAR(128) PRIMARY KEY,
        customer_id VARCHAR(64),
        total_amount DECIMAL(18,2) DEFAULT 0.00,
        taxable_amount DECIMAL(18,2) DEFAULT 0.00,
        amount_paid DECIMAL(18,2) DEFAULT 0.00,
        igst_amount DECIMAL(18,2) DEFAULT 0.00,
        cgst_amount DECIMAL(18,2) DEFAULT 0.00,
        sgst_amount DECIMAL(18,2) DEFAULT 0.00,
        due_date DATE,
        invoice_date DATE,
        status VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create finance_purchase_orders table
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_purchase_orders (
        po_number VARCHAR(128) PRIMARY KEY,
        customer_id VARCHAR(64),
        po_total_value DECIMAL(18,2) DEFAULT 0.00,
        po_date DATE,
        po_status VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create finance_payments table
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_payments (
        payment_id VARCHAR(128) PRIMARY KEY,
        customer_id VARCHAR(64),
        amount DECIMAL(18,2) DEFAULT 0.00,
        payment_date DATE,
        receipt_number VARCHAR(128),
        status VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create finance_quotations table
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_quotations (
        quotation_number VARCHAR(128) PRIMARY KEY,
        customer_id VARCHAR(64),
        quotation_amount DECIMAL(18,2) DEFAULT 0.00,
        quotation_date DATE,
        status VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "✅ Finance tables created successfully\n\n";
    
    // Insert mock customers
    $customers = [
        ['CUST001', 'ABC Industries Ltd', '29ABCDE1234F1Z5'],
        ['CUST002', 'XYZ Corporation', '29FGHIJ5678K1L5'],
        ['CUST003', 'PQR Enterprises', '29MNOPQ9012R1S5'],
        ['CUST004', 'LMN Solutions', '29TUVWX3456Y1Z5'],
        ['CUST005', 'DEF Technologies', '29ABCDE7890F1G5']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE customer_name = VALUES(customer_name)");
    foreach ($customers as $customer) {
        $stmt->execute($customer);
    }
    echo "✅ Inserted " . count($customers) . " mock customers\n";
    
    // Insert mock invoices
    $invoices = [
        ['INV-2024-001', 'CUST001', 50000.00, 42372.88, 25000.00, 0.00, 3813.56, 3813.56, '2024-02-15', '2024-01-15', 'partially_paid'],
        ['INV-2024-002', 'CUST002', 75000.00, 63559.32, 75000.00, 0.00, 5720.34, 5720.34, '2024-02-20', '2024-01-20', 'paid'],
        ['INV-2024-003', 'CUST003', 120000.00, 101694.92, 0.00, 0.00, 9152.54, 9152.54, '2024-03-10', '2024-02-10', 'pending'],
        ['INV-2024-004', 'CUST004', 85000.00, 72033.90, 42500.00, 0.00, 6483.05, 6483.05, '2024-03-15', '2024-02-15', 'partially_paid'],
        ['INV-2024-005', 'CUST005', 95000.00, 80508.47, 95000.00, 0.00, 7245.76, 7245.76, '2024-03-20', '2024-02-20', 'paid']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount)");
    foreach ($invoices as $invoice) {
        $stmt->execute($invoice);
    }
    echo "✅ Inserted " . count($invoices) . " mock invoices\n";
    
    // Insert mock purchase orders
    $pos = [
        ['PO-2024-001', 'CUST001', 60000.00, '2024-01-10', 'confirmed'],
        ['PO-2024-002', 'CUST002', 80000.00, '2024-01-15', 'confirmed'],
        ['PO-2024-003', 'CUST003', 150000.00, '2024-02-05', 'pending'],
        ['PO-2024-004', 'CUST004', 90000.00, '2024-02-10', 'confirmed'],
        ['PO-2024-005', 'CUST005', 110000.00, '2024-02-15', 'confirmed']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE po_total_value = VALUES(po_total_value)");
    foreach ($pos as $po) {
        $stmt->execute($po);
    }
    echo "✅ Inserted " . count($pos) . " mock purchase orders\n";
    
    // Insert mock payments
    $payments = [
        ['PAY-2024-001', 'CUST001', 25000.00, '2024-01-25', 'RCP-001', 'received'],
        ['PAY-2024-002', 'CUST002', 75000.00, '2024-02-05', 'RCP-002', 'received'],
        ['PAY-2024-003', 'CUST004', 42500.00, '2024-02-28', 'RCP-003', 'received'],
        ['PAY-2024-004', 'CUST005', 95000.00, '2024-03-05', 'RCP-004', 'received']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_payments (payment_id, customer_id, amount, payment_date, receipt_number, status) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)");
    foreach ($payments as $payment) {
        $stmt->execute($payment);
    }
    echo "✅ Inserted " . count($payments) . " mock payments\n";
    
    // Insert mock quotations
    $quotations = [
        ['QUO-2024-001', 'CUST001', 55000.00, '2024-01-05', 'sent'],
        ['QUO-2024-002', 'CUST002', 78000.00, '2024-01-10', 'accepted'],
        ['QUO-2024-003', 'CUST003', 125000.00, '2024-01-30', 'accepted'],
        ['QUO-2024-004', 'CUST004', 88000.00, '2024-02-05', 'accepted'],
        ['QUO-2024-005', 'CUST005', 98000.00, '2024-02-10', 'accepted']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_quotations (quotation_number, customer_id, quotation_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quotation_amount = VALUES(quotation_amount)");
    foreach ($quotations as $quotation) {
        $stmt->execute($quotation);
    }
    echo "✅ Inserted " . count($quotations) . " mock quotations\n";
    
    // Create consolidated view
    $mysql->exec("CREATE TABLE IF NOT EXISTS finance_consolidated (
        id INT AUTO_INCREMENT PRIMARY KEY,
        record_type VARCHAR(32) NOT NULL,
        document_number VARCHAR(128) NOT NULL,
        customer_id VARCHAR(64),
        customer_name VARCHAR(255),
        customer_gstin VARCHAR(64),
        amount DECIMAL(18,2) DEFAULT 0.00,
        taxable_amount DECIMAL(18,2) DEFAULT 0.00,
        amount_paid DECIMAL(18,2) DEFAULT 0.00,
        outstanding_amount DECIMAL(18,2) DEFAULT 0.00,
        igst DECIMAL(18,2) DEFAULT 0.00,
        cgst DECIMAL(18,2) DEFAULT 0.00,
        sgst DECIMAL(18,2) DEFAULT 0.00,
        due_date DATE,
        invoice_date DATE,
        status VARCHAR(64),
        company_prefix VARCHAR(32) NOT NULL DEFAULT 'ERGN',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_record (record_type, document_number, company_prefix)
    )");
    
    // Populate consolidated table
    $mysql->exec("INSERT INTO finance_consolidated (record_type, document_number, customer_id, amount, status, company_prefix)
        SELECT 'invoice', invoice_number, customer_id, total_amount, status, 'ERGN' FROM finance_invoices
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status)");
    
    $mysql->exec("INSERT INTO finance_consolidated (record_type, document_number, customer_id, amount, status, company_prefix)
        SELECT 'purchase_order', po_number, customer_id, po_total_value, po_status, 'ERGN' FROM finance_purchase_orders
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status)");
    
    $mysql->exec("INSERT INTO finance_consolidated (record_type, document_number, customer_id, amount, status, company_prefix)
        SELECT 'payment', payment_id, customer_id, amount, status, 'ERGN' FROM finance_payments
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status)");
    
    echo "✅ Created consolidated finance view\n";
    
    // Log successful mock data creation
    $mysql->exec("INSERT INTO sync_log (table_name, records_synced, sync_status, error_message, sync_started_at, sync_completed_at) 
        VALUES ('mock_data_creation', " . (count($customers) + count($invoices) + count($pos) + count($payments) + count($quotations)) . ", 'completed', 'Mock data created for testing', NOW(), NOW())");
    
    echo "\n🎉 Mock finance data setup completed successfully!\n";
    echo "📊 Summary:\n";
    echo "   - " . count($customers) . " customers\n";
    echo "   - " . count($invoices) . " invoices\n";
    echo "   - " . count($pos) . " purchase orders\n";
    echo "   - " . count($payments) . " payments\n";
    echo "   - " . count($quotations) . " quotations\n";
    echo "\n💡 You can now use the finance module even without PostgreSQL connection!\n";
    echo "🔄 When PostgreSQL becomes available, run the sync again to get real data.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating mock data: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>