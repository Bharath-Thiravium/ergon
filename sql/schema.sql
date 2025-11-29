-- Finance Consolidated Table
CREATE TABLE IF NOT EXISTS finance_consolidated (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_type VARCHAR(20) NOT NULL DEFAULT 'invoice',
    document_number VARCHAR(100) NOT NULL,
    customer_id VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_gstin VARCHAR(15),
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    taxable_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    outstanding_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    igst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    cgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    sgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    due_date DATE,
    invoice_date DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    company_prefix VARCHAR(10) NOT NULL,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Unique constraint for idempotency
    UNIQUE KEY uk_company_document (company_prefix, document_number),
    
    -- Performance indexes
    INDEX idx_company_prefix (company_prefix),
    INDEX idx_record_type (record_type),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_outstanding (outstanding_amount),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at),
    
    -- Composite indexes for common queries
    INDEX idx_company_record_type (company_prefix, record_type),
    INDEX idx_company_status (company_prefix, status),
    INDEX idx_company_customer (company_prefix, customer_id),
    INDEX idx_company_status_due (company_prefix, status, due_date),
    INDEX idx_status_outstanding (status, outstanding_amount),
    INDEX idx_due_outstanding (due_date, outstanding_amount),
    INDEX idx_company_created (company_prefix, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard Stats Table
CREATE TABLE IF NOT EXISTS dashboard_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_prefix VARCHAR(10) NOT NULL,
    expected_inflow DECIMAL(18,2) DEFAULT 0.00,
    po_commitments DECIMAL(18,2) DEFAULT 0.00,
    net_cash_flow DECIMAL(18,2) DEFAULT 0.00,
    last_computed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_company_prefix (company_prefix),
    INDEX idx_last_computed (last_computed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Metadata Table
CREATE TABLE IF NOT EXISTS sync_metadata (
    company_prefix VARCHAR(10) PRIMARY KEY,
    last_sync_invoices TIMESTAMP NULL,
    last_sync_activities TIMESTAMP NULL,
    last_sync_cashflow TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Runs Table
CREATE TABLE IF NOT EXISTS sync_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_prefix VARCHAR(10) NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NOT NULL,
    rows_fetched INT NOT NULL DEFAULT 0,
    rows_upserted INT NOT NULL DEFAULT 0,
    errors_count INT NOT NULL DEFAULT 0,
    status ENUM('success', 'partial_failure', 'failure') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_prefix (company_prefix),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_company_status (company_prefix, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Errors Table
CREATE TABLE IF NOT EXISTS sync_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(100) NOT NULL,
    company_prefix VARCHAR(10) NOT NULL,
    error_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_prefix (company_prefix),
    INDEX idx_document_number (document_number),
    INDEX idx_error_type (error_type),
    INDEX idx_created_at (created_at),
    INDEX idx_company_document (company_prefix, document_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;