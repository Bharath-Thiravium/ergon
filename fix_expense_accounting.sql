-- Fix for Expense Approval Accounting Issue
-- This script creates the necessary accounting tables and updates the expense approval process

-- Create accounts table for financial tracking
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    account_code VARCHAR(20) UNIQUE,
    balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create journal_entries table for double-entry bookkeeping
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type VARCHAR(50) NOT NULL, -- 'expense', 'advance', etc.
    reference_id INT NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT,
    total_amount DECIMAL(15,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create journal_entry_lines table for individual debit/credit entries
CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,
    description TEXT,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Insert default chart of accounts
INSERT IGNORE INTO accounts (account_name, account_type, account_code) VALUES
('Cash', 'asset', 'A001'),
('Accounts Payable', 'liability', 'L001'),
('Employee Expenses', 'expense', 'E001'),
('Travel Expenses', 'expense', 'E002'),
('Office Expenses', 'expense', 'E003'),
('Miscellaneous Expenses', 'expense', 'E004');

-- Add missing columns to expenses table (skip rejection_reason as it already exists)
ALTER TABLE expenses ADD COLUMN approved_by INT;
ALTER TABLE expenses ADD COLUMN approved_at TIMESTAMP NULL;
ALTER TABLE expenses ADD COLUMN journal_entry_id INT;

-- Add foreign key constraints
ALTER TABLE expenses ADD FOREIGN KEY (approved_by) REFERENCES users(id);
ALTER TABLE expenses ADD FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id);

-- Create missing sla_history table
CREATE TABLE IF NOT EXISTS sla_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    notes TEXT,
    INDEX idx_daily_task_id (daily_task_id)
);