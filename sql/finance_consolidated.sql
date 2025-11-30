CREATE TABLE finance_consolidated (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  record_type ENUM('quotation', 'purchase_order', 'invoice', 'payment') NOT NULL,
  document_number VARCHAR(255) NOT NULL,
  customer_id BIGINT,
  customer_name VARCHAR(255),
  amount DECIMAL(18,2) DEFAULT 0,
  status VARCHAR(255),
  created_at TIMESTAMP,
  company_prefix VARCHAR(10),
  INDEX idx_prefix_type (company_prefix, record_type),
  INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;