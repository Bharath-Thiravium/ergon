-- Quick fix for missing approved_at column
ALTER TABLE expenses ADD COLUMN approved_at TIMESTAMP NULL;