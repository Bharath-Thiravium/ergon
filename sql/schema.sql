-- sql/schema.sql (MySQL / MariaDB)
CREATE TABLE IF NOT EXISTS finance_consolidated (
  id INT AUTO_INCREMENT PRIMARY KEY,
  record_type VARCHAR(32) NOT NULL,         -- 'invoice','quotation','purchase_order','payment'
  document_number VARCHAR(128) NOT NULL,    -- invoice_number, quotation_number, po_number, payment_id
  customer_id VARCHAR(64) DEFAULT NULL,
  customer_name VARCHAR(255) DEFAULT NULL,
  customer_gstin VARCHAR(64) DEFAULT NULL,
  amount DECIMAL(18,2) DEFAULT 0.00,        -- total or PO amount or quotation amount
  taxable_amount DECIMAL(18,2) DEFAULT 0.00,
  amount_paid DECIMAL(18,2) DEFAULT 0.00,
  outstanding_amount DECIMAL(18,2) DEFAULT 0.00,
  igst DECIMAL(18,2) DEFAULT 0.00,
  cgst DECIMAL(18,2) DEFAULT 0.00,
  sgst DECIMAL(18,2) DEFAULT 0.00,
  due_date DATE DEFAULT NULL,
  invoice_date DATE DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  company_prefix VARCHAR(32) NOT NULL,
  raw_data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_company_doc (company_prefix, document_number),
  INDEX idx_company_prefix (company_prefix),
  INDEX idx_company_record (company_prefix, record_type),
  INDEX idx_company_customer (company_prefix, customer_id),
  INDEX idx_status_due (company_prefix, status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dashboard_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_prefix VARCHAR(32) NOT NULL UNIQUE,
  expected_inflow DECIMAL(18,2) DEFAULT 0.00,
  po_commitments DECIMAL(18,2) DEFAULT 0.00,
  net_cash_flow DECIMAL(18,2) DEFAULT 0.00,
  last_computed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_metadata (
  company_prefix VARCHAR(32) PRIMARY KEY,
  last_sync_invoices TIMESTAMP NULL,
  last_sync_activities TIMESTAMP NULL,
  last_sync_cashflow TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  run_type VARCHAR(64) NOT NULL, -- 'invoices','activities','cashflow'
  company_prefix VARCHAR(32) NOT NULL,
  started_at TIMESTAMP NOT NULL,
  ended_at TIMESTAMP NULL,
  rows_fetched INT DEFAULT 0,
  rows_upserted INT DEFAULT 0,
  errors_count INT DEFAULT 0,
  status VARCHAR(32) DEFAULT 'completed', -- 'completed','partial_failure','failed'
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_errors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  run_id INT DEFAULT NULL,
  company_prefix VARCHAR(32) NOT NULL,
  record_type VARCHAR(32) NULL,
  document_number VARCHAR(128) NULL,
  error_type VARCHAR(128) NOT NULL,
  message TEXT,
  raw_data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;