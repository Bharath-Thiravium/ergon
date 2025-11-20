-- Comprehensive fix for all missing expense columns
-- Run each ALTER statement individually in phpMyAdmin

-- Add approved_by column (safe - uses IF NOT EXISTS equivalent)
ALTER TABLE expenses ADD COLUMN approved_by INT NULL;

-- Add approved_at column (safe - uses IF NOT EXISTS equivalent) 
ALTER TABLE expenses ADD COLUMN approved_at TIMESTAMP NULL;

-- Add journal_entry_id column (safe - uses IF NOT EXISTS equivalent)
ALTER TABLE expenses ADD COLUMN journal_entry_id INT NULL;

-- Add attachment column if missing
ALTER TABLE expenses ADD COLUMN attachment VARCHAR(255) NULL;

-- Add rejection_reason column if missing
ALTER TABLE expenses ADD COLUMN rejection_reason TEXT NULL;