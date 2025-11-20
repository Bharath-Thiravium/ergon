-- Direct SQL fix for expense approval/rejection errors
-- Run this in phpMyAdmin or MySQL command line

-- Step 1: Add missing columns to expenses table
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS approved_by INT NULL;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS journal_entry_id INT NULL;

-- Step 2: Create accounting tables
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

CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_journal_entry (journal_entry_id),
    INDEX idx_account (account_id)
);

-- Step 3: Insert default accounts
INSERT IGNORE INTO accounts (account_code, account_name, account_type, balance) VALUES
('E001', 'General Expenses', 'expense', 0.00),
('E002', 'Travel Expenses', 'expense', 0.00),
('E003', 'Office Expenses', 'expense', 0.00),
('E004', 'Miscellaneous Expenses', 'expense', 0.00),
('L001', 'Accounts Payable', 'liability', 0.00),
('A001', 'Cash', 'asset', 0.00),
('A002', 'Bank Account', 'asset', 0.00);

-- Step 4: Verify the fix
SELECT 'Expenses table columns:' as info;
SHOW COLUMNS FROM expenses WHERE Field IN ('approved_by', 'approved_at', 'journal_entry_id');

SELECT 'Account records:' as info;
SELECT account_code, account_name, account_type FROM accounts ORDER BY account_code;