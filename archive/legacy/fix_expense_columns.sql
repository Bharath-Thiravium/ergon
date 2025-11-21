-- Fix missing columns in expenses table
-- This script adds the missing columns that are causing the SQL errors

-- Add approved_by column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'expenses' 
     AND column_name = 'approved_by' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE expenses ADD COLUMN approved_by INT NULL',
    'SELECT "approved_by column already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add journal_entry_id column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'expenses' 
     AND column_name = 'journal_entry_id' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE expenses ADD COLUMN journal_entry_id INT NULL',
    'SELECT "journal_entry_id column already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the columns were added
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'expenses' 
AND COLUMN_NAME IN ('approved_by', 'journal_entry_id')
ORDER BY COLUMN_NAME;