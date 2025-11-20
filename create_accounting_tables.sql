-- Create accounting tables if they don't exist
-- These tables are required for the expense approval accounting system

-- Create accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(10) NOT NULL UNIQUE,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create journal_entries table
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type VARCHAR(50) NOT NULL,
    reference_id INT NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT,
    total_amount DECIMAL(15,2) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create journal_entry_lines table
CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Insert default accounts if they don't exist
INSERT IGNORE INTO accounts (account_code, account_name, account_type, balance) VALUES
('E001', 'General Expenses', 'expense', 0.00),
('E002', 'Travel Expenses', 'expense', 0.00),
('E003', 'Office Expenses', 'expense', 0.00),
('E004', 'Miscellaneous Expenses', 'expense', 0.00),
('L001', 'Accounts Payable', 'liability', 0.00),
('A001', 'Cash', 'asset', 0.00),
('A002', 'Bank Account', 'asset', 0.00);

-- Verify tables were created
SELECT 'accounts' as table_name, COUNT(*) as record_count FROM accounts
UNION ALL
SELECT 'journal_entries' as table_name, COUNT(*) as record_count FROM journal_entries
UNION ALL
SELECT 'journal_entry_lines' as table_name, COUNT(*) as record_count FROM journal_entry_lines;