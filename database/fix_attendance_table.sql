-- Fix attendance table structure to match controller expectations
-- This script will update the attendance table to use DATETIME columns instead of TIME

-- First, check if we need to rename columns
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='clock_in' AND table_schema=DATABASE()) > 0,
    'ALTER TABLE attendance CHANGE COLUMN clock_in check_in DATETIME NULL',
    'SELECT "clock_in column does not exist or already renamed"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='clock_out' AND table_schema=DATABASE()) > 0,
    'ALTER TABLE attendance CHANGE COLUMN clock_out check_out DATETIME NULL',
    'SELECT "clock_out column does not exist or already renamed"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add missing columns if they don't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='check_in' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN check_in DATETIME NOT NULL',
    'SELECT "check_in exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='check_out' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN check_out DATETIME NULL',
    'SELECT "check_out exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='latitude' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10, 8) NULL',
    'SELECT "latitude exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='longitude' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11, 8) NULL',
    'SELECT "longitude exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='location_name' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN location_name VARCHAR(255) DEFAULT "Office"',
    'SELECT "location_name exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='updated_at' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE attendance ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Remove the old date column if it exists (since we're using DATETIME now)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance' AND column_name='date' AND table_schema=DATABASE()) > 0,
    'ALTER TABLE attendance DROP COLUMN date',
    'SELECT "date column does not exist"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_user_id ON attendance(user_id);
CREATE INDEX IF NOT EXISTS idx_check_in_date ON attendance(check_in);