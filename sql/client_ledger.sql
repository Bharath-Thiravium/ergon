-- Customer Ledger Tables
-- Run once to create tables (controller also auto-creates them on first access)

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_ledgers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    entry_type ENUM('payment_received','payment_sent','adjustment') NOT NULL,
    direction ENUM('debit','credit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    description TEXT,
    reference_no VARCHAR(100),
    transaction_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for fast per-client ledger queries
CREATE INDEX IF NOT EXISTS idx_client_ledgers_client_date
    ON client_ledgers (client_id, transaction_date DESC, id DESC);
