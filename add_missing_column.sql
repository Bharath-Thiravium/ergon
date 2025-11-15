-- Add rejection_reason to expenses table (ignore error if exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'expenses' 
     AND column_name = 'rejection_reason' 
     AND table_schema = DATABASE()) > 0,
    'SELECT 1',
    'ALTER TABLE expenses ADD COLUMN rejection_reason TEXT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rejection_reason to leaves table (ignore error if exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'leaves' 
     AND column_name = 'rejection_reason' 
     AND table_schema = DATABASE()) > 0,
    'SELECT 1',
    'ALTER TABLE leaves ADD COLUMN rejection_reason TEXT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;