-- Finance Module Schema - Mirror PostgreSQL Tables

CREATE TABLE IF NOT EXISTS finance_customers (
  customer_id VARCHAR(64) PRIMARY KEY,
  customer_name VARCHAR(255) DEFAULT NULL,
  customer_gstin VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_quotations (
  quotation_number VARCHAR(128) PRIMARY KEY,
  customer_id VARCHAR(64) DEFAULT NULL,
  quotation_amount DECIMAL(18,2) DEFAULT 0.00,
  quotation_date DATE DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_purchase_orders (
  po_number VARCHAR(128) PRIMARY KEY,
  customer_id VARCHAR(64) DEFAULT NULL,
  po_total_value DECIMAL(18,2) DEFAULT 0.00,
  po_date DATE DEFAULT NULL,
  po_status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_invoices (
  invoice_number VARCHAR(128) PRIMARY KEY,
  customer_id VARCHAR(64) DEFAULT NULL,
  total_amount DECIMAL(18,2) DEFAULT 0.00,
  taxable_amount DECIMAL(18,2) DEFAULT 0.00,
  amount_paid DECIMAL(18,2) DEFAULT 0.00,
  igst_amount DECIMAL(18,2) DEFAULT 0.00,
  cgst_amount DECIMAL(18,2) DEFAULT 0.00,
  sgst_amount DECIMAL(18,2) DEFAULT 0.00,
  due_date DATE DEFAULT NULL,
  invoice_date DATE DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_id (customer_id),
  INDEX idx_status (status),
  INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_payments (
  payment_id VARCHAR(128) PRIMARY KEY,
  customer_id VARCHAR(64) DEFAULT NULL,
  amount DECIMAL(18,2) DEFAULT 0.00,
  payment_date DATE DEFAULT NULL,
  receipt_number VARCHAR(128) DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
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