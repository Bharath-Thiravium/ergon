<?php
/**
 * Setup script for New Finance Module
 * Run this once to create the required database tables
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Setting up New Finance Module...\n";
    
    // Settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created finance_settings table\n";
    
    // Stats table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            total_revenue DECIMAL(15,2) DEFAULT 0,
            amount_received DECIMAL(15,2) DEFAULT 0,
            outstanding_amount DECIMAL(15,2) DEFAULT 0,
            gst_liability DECIMAL(15,2) DEFAULT 0,
            po_commitments DECIMAL(15,2) DEFAULT 0,
            claimable_amount DECIMAL(15,2) DEFAULT 0,
            collection_rate DECIMAL(5,2) DEFAULT 0,
            overdue_amount DECIMAL(15,2) DEFAULT 0,
            open_pos INT DEFAULT 0,
            closed_pos INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_prefix (company_prefix)
        )
    ");
    echo "✓ Created finance_stats table\n";
    
    // Funnel table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_funnel (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            quotations_count INT DEFAULT 0,
            quotations_value DECIMAL(15,2) DEFAULT 0,
            po_count INT DEFAULT 0,
            po_value DECIMAL(15,2) DEFAULT 0,
            invoices_count INT DEFAULT 0,
            invoices_value DECIMAL(15,2) DEFAULT 0,
            payments_count INT DEFAULT 0,
            payments_value DECIMAL(15,2) DEFAULT 0,
            quotation_to_po DECIMAL(5,2) DEFAULT 0,
            po_to_invoice DECIMAL(5,2) DEFAULT 0,
            invoice_to_payment DECIMAL(5,2) DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_prefix (company_prefix)
        )
    ");
    echo "✓ Created finance_funnel table\n";
    
    // Invoices table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            invoice_number VARCHAR(100),
            customer_name VARCHAR(255),
            customer_gstin VARCHAR(50),
            total_amount DECIMAL(15,2) DEFAULT 0,
            taxable_amount DECIMAL(15,2) DEFAULT 0,
            amount_paid DECIMAL(15,2) DEFAULT 0,
            outstanding_amount DECIMAL(15,2) DEFAULT 0,
            igst DECIMAL(15,2) DEFAULT 0,
            cgst DECIMAL(15,2) DEFAULT 0,
            sgst DECIMAL(15,2) DEFAULT 0,
            invoice_date DATE,
            due_date DATE,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_prefix (company_prefix),
            INDEX idx_invoice_number (invoice_number),
            INDEX idx_outstanding (outstanding_amount)
        )
    ");
    echo "✓ Created finance_invoices table\n";
    
    // Quotations table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_quotations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            quotation_number VARCHAR(100),
            customer_name VARCHAR(255),
            total_amount DECIMAL(15,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'pending',
            created_date DATE,
            valid_until DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_prefix (company_prefix),
            INDEX idx_quotation_number (quotation_number)
        )
    ");
    echo "✓ Created finance_quotations table\n";
    
    // Purchase Orders table
    $db->exec("
        CREATE TABLE IF NOT EXISTS finance_purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            po_number VARCHAR(100),
            internal_po_number VARCHAR(100),
            vendor_name VARCHAR(255),
            total_amount DECIMAL(15,2) DEFAULT 0,
            amount_paid DECIMAL(15,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'open',
            po_date DATE,
            received_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_prefix (company_prefix),
            INDEX idx_po_number (po_number)
        )
    ");
    echo "✓ Created finance_purchase_orders table\n";
    
    // Set default company prefix
    $stmt = $db->prepare("
        INSERT INTO finance_settings (setting_key, setting_value) 
        VALUES ('company_prefix', '') 
        ON DUPLICATE KEY UPDATE setting_key = setting_key
    ");
    $stmt->execute();
    echo "✓ Set default company prefix\n";
    
    echo "\n🎉 New Finance Module setup completed successfully!\n";
    echo "You can now access the new finance dashboard at: /ergon/finance\n";
    echo "The old finance module is still available at: /ergon/finance/old\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up New Finance Module: " . $e->getMessage() . "\n";
    exit(1);
}
?>