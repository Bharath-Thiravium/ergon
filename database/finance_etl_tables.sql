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
    customer_count INT DEFAULT 0,
    po_commitments DECIMAL(15,2) DEFAULT 0,
    open_pos INT DEFAULT 0,
    closed_pos INT DEFAULT 0,
    claimable_amount DECIMAL(15,2) DEFAULT 0,
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

-- Sample Data Insert (for testing)
-- INSERT INTO finance_consolidated (record_type, document_number, customer_name, amount, company_prefix) 
-- VALUES ('invoice', 'BKC001', 'Test Customer', 10000.00, 'BKC');