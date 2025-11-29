-- Finance ETL Database Tables
-- Run this SQL to create the required tables for the ETL finance module

-- 1. Consolidated Finance Data Table (Main ETL Output)
CREATE TABLE IF NOT EXISTS finance_consolidated (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_type ENUM('invoice', 'quotation', 'purchase_order', 'payment') NOT NULL,
    document_number VARCHAR(100),
    customer_id VARCHAR(50),
    customer_name VARCHAR(255),
    amount DECIMAL(15,2) DEFAULT 0,
    taxable_amount DECIMAL(15,2) DEFAULT 0,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    outstanding_amount DECIMAL(15,2) DEFAULT 0,
    igst DECIMAL(15,2) DEFAULT 0,
    cgst DECIMAL(15,2) DEFAULT 0,
    sgst DECIMAL(15,2) DEFAULT 0,
    due_date DATE NULL,
    invoice_date DATE NULL,
    status VARCHAR(50),
    company_prefix VARCHAR(10),
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for fast analytics
    INDEX idx_record_type (record_type),
    INDEX idx_company_prefix (company_prefix),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_outstanding (outstanding_amount),
    INDEX idx_composite (company_prefix, record_type, status),
    INDEX idx_customer_name (customer_name),
    INDEX idx_document_number (document_number)
);

-- 2. Dashboard Stats Table (Pre-calculated Analytics)
CREATE TABLE IF NOT EXISTS dashboard_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_prefix VARCHAR(10),
    total_revenue DECIMAL(15,2) DEFAULT 0,
    invoice_count INT DEFAULT 0,
    amount_received DECIMAL(15,2) DEFAULT 0,
    outstanding_amount DECIMAL(15,2) DEFAULT 0,
    pending_invoices INT DEFAULT 0,
    customers_pending INT DEFAULT 0,
    overdue_amount DECIMAL(15,2) DEFAULT 0,
    outstanding_percentage DECIMAL(5,2) DEFAULT 0,
    customer_count INT DEFAULT 0,
    po_commitments DECIMAL(15,2) DEFAULT 0,
    open_pos INT DEFAULT 0,
    closed_pos INT DEFAULT 0,
    claimable_amount DECIMAL(15,2) DEFAULT 0,
    claimable_pos INT DEFAULT 0,
    claim_rate DECIMAL(5,2) DEFAULT 0,
    igst_liability DECIMAL(15,2) DEFAULT 0,
    cgst_sgst_total DECIMAL(15,2) DEFAULT 0,
    gst_liability DECIMAL(15,2) DEFAULT 0,
    placed_quotations INT DEFAULT 0,
    rejected_quotations INT DEFAULT 0,
    pending_quotations INT DEFAULT 0,
    total_quotations INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_prefix (company_prefix),
    INDEX idx_generated_at (generated_at)
);

-- 3. Funnel Stats Table (Conversion Analytics)
CREATE TABLE IF NOT EXISTS funnel_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_prefix VARCHAR(10),
    quotation_count INT DEFAULT 0,
    quotation_value DECIMAL(15,2) DEFAULT 0,
    po_count INT DEFAULT 0,
    po_value DECIMAL(15,2) DEFAULT 0,
    po_conversion_rate DECIMAL(5,2) DEFAULT 0,
    invoice_count INT DEFAULT 0,
    invoice_value DECIMAL(15,2) DEFAULT 0,
    invoice_conversion_rate DECIMAL(5,2) DEFAULT 0,
    payment_count INT DEFAULT 0,
    payment_value DECIMAL(15,2) DEFAULT 0,
    payment_conversion_rate DECIMAL(5,2) DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_prefix_funnel (company_prefix),
    INDEX idx_generated_funnel (generated_at)
);

-- 4. Legacy Finance Tables (Keep for backward compatibility)
CREATE TABLE IF NOT EXISTS finance_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) UNIQUE,
    record_count INT,
    last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_prefix VARCHAR(10) DEFAULT 'BKC'
);

CREATE TABLE IF NOT EXISTS finance_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100),
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(table_name)
);

-- Initialize dashboard_stats for all company prefixes
INSERT INTO dashboard_stats (company_prefix) VALUES 
('BKGE'), ('SE'), ('TC'), ('BKC')
ON DUPLICATE KEY UPDATE company_prefix = VALUES(company_prefix);

-- Initialize funnel_stats with sample data
INSERT INTO funnel_stats (company_prefix, quotation_count, quotation_value, po_count, po_value, po_conversion_rate, invoice_count, invoice_value, invoice_conversion_rate, payment_count, payment_value, payment_conversion_rate) VALUES 
('TC', 1, 150000.00, 1, 85000.00, 56.7, 2, 358078.00, 421.3, 1, 59000.00, 16.5)
ON DUPLICATE KEY UPDATE 
    quotation_count = VALUES(quotation_count),
    quotation_value = VALUES(quotation_value),
    po_count = VALUES(po_count),
    po_value = VALUES(po_value),
    po_conversion_rate = VALUES(po_conversion_rate),
    invoice_count = VALUES(invoice_count),
    invoice_value = VALUES(invoice_value),
    invoice_conversion_rate = VALUES(invoice_conversion_rate),
    payment_count = VALUES(payment_count),
    payment_value = VALUES(payment_value),
    payment_conversion_rate = VALUES(payment_conversion_rate);

-- Add performance indexes
ALTER TABLE dashboard_stats ADD INDEX IF NOT EXISTS idx_generated_at (generated_at);
ALTER TABLE finance_consolidated ADD INDEX IF NOT EXISTS idx_customer_name (customer_name);
ALTER TABLE finance_consolidated ADD INDEX IF NOT EXISTS idx_document_number (document_number);
ALTER TABLE finance_consolidated ADD INDEX IF NOT EXISTS idx_record_status (company_prefix, record_type, status);

-- Sample Data Insert (for testing)
INSERT INTO finance_consolidated (record_type, document_number, customer_name, amount, taxable_amount, amount_paid, outstanding_amount, company_prefix, status) VALUES 
('invoice', 'TC001', 'ABC Corp', 240078.00, 200000.00, 0.00, 240078.00, 'TC', 'pending'),
('invoice', 'TC002', 'XYZ Ltd', 118000.00, 100000.00, 59000.00, 59000.00, 'TC', 'partial'),
('quotation', 'TC-Q001', 'DEF Industries', 150000.00, 150000.00, 0.00, 0.00, 'TC', 'pending'),
('purchase_order', 'TC-PO001', 'GHI Suppliers', 85000.00, 85000.00, 25000.00, 60000.00, 'TC', 'open'),
('payment', 'TC-PAY001', 'ABC Corp', 59000.00, 59000.00, 59000.00, 0.00, 'TC', 'completed')
ON DUPLICATE KEY UPDATE id=id;

-- Update dashboard_stats with calculated values
UPDATE dashboard_stats SET 
    total_revenue = 358078.00,
    invoice_count = 2,
    amount_received = 59000.00,
    outstanding_amount = 299078.00,
    pending_invoices = 2,
    customers_pending = 2,
    overdue_amount = 240078.00,
    outstanding_percentage = 83.5,
    customer_count = 3,
    po_commitments = 85000.00,
    open_pos = 1,
    closed_pos = 0,
    claimable_amount = 299078.00,
    claimable_pos = 2,
    claim_rate = 83.5,
    generated_at = NOW()
WHERE company_prefix = 'TC';