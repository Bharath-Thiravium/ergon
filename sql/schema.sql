-- Finance Module Schema
-- Run setup_finance_db.php to apply this on a new server

CREATE TABLE IF NOT EXISTS finance_companies (
  company_id INT PRIMARY KEY,
  company_prefix VARCHAR(32) NOT NULL,
  company_name VARCHAR(255) NOT NULL,
  INDEX idx_prefix (company_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_customers (
  customer_id BIGINT PRIMARY KEY,
  customer_name VARCHAR(255) DEFAULT NULL,
  customer_gstin VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_quotations (
  id BIGINT PRIMARY KEY,
  quotation_number VARCHAR(255) DEFAULT NULL,
  customer_id BIGINT DEFAULT NULL,
  company_id BIGINT DEFAULT NULL,
  quotation_amount DECIMAL(18,2) DEFAULT 0.00,
  quotation_date DATE DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_company_id (company_id),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_purchase_orders (
  id BIGINT PRIMARY KEY,
  po_number VARCHAR(255) DEFAULT NULL,
  customer_id BIGINT DEFAULT NULL,
  company_id BIGINT DEFAULT NULL,
  po_total_value DECIMAL(18,2) DEFAULT 0.00,
  po_date DATE DEFAULT NULL,
  po_status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_company_id (company_id),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_invoices (
  id BIGINT PRIMARY KEY,
  invoice_number VARCHAR(255) DEFAULT NULL,
  customer_id BIGINT DEFAULT NULL,
  company_id BIGINT DEFAULT NULL,
  total_amount DECIMAL(18,2) DEFAULT 0.00,
  taxable_amount DECIMAL(18,2) DEFAULT 0.00,
  amount_paid DECIMAL(18,2) DEFAULT 0.00,
  outstanding_amount DECIMAL(18,2) DEFAULT 0.00,
  igst_amount DECIMAL(18,2) DEFAULT 0.00,
  cgst_amount DECIMAL(18,2) DEFAULT 0.00,
  sgst_amount DECIMAL(18,2) DEFAULT 0.00,
  due_date DATE DEFAULT NULL,
  invoice_date DATE DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_company_id (company_id),
  INDEX idx_customer_id (customer_id),
  INDEX idx_status (status),
  INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_payments (
  id BIGINT PRIMARY KEY,
  payment_number VARCHAR(255) DEFAULT NULL,
  customer_id BIGINT DEFAULT NULL,
  company_id BIGINT DEFAULT NULL,
  amount DECIMAL(18,2) DEFAULT 0.00,
  payment_date DATE DEFAULT NULL,
  receipt_number VARCHAR(128) DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_company_id (company_id),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_name VARCHAR(64) NOT NULL,
  records_synced INT DEFAULT 0,
  sync_status VARCHAR(32) DEFAULT 'completed',
  error_message TEXT DEFAULT NULL,
  sync_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  sync_completed_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
