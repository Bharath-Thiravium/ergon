-- Complete fix for attendance table structure
-- This script will standardize the attendance table to work with both controllers

-- First, backup existing data
DROP TABLE IF EXISTS attendance_backup_temp;
CREATE TABLE attendance_backup_temp AS SELECT * FROM attendance WHERE 1=1;

-- Check existing table structure
SET @has_clock_in = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance_backup_temp' AND column_name='clock_in' AND table_schema=DATABASE());
SET @has_check_in = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance_backup_temp' AND column_name='check_in' AND table_schema=DATABASE());
SET @has_date = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='attendance_backup_temp' AND column_name='date' AND table_schema=DATABASE());

-- Drop and recreate attendance table with proper structure
DROP TABLE IF EXISTS attendance;
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    location_name VARCHAR(255) DEFAULT 'Office',
    status VARCHAR(20) DEFAULT 'present',
    shift_id INT NULL,
    total_hours DECIMAL(5,2) NULL,
    ip_address VARCHAR(45) NULL,
    device_info TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_check_in_date (check_in),
    INDEX idx_user_date (user_id, check_in),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Migrate data based on existing structure
-- Case 1: Old structure with clock_in/clock_out + date columns
INSERT IGNORE INTO attendance (user_id, check_in, check_out, status, created_at)
SELECT 
    user_id,
    CASE 
        WHEN @has_clock_in > 0 AND @has_date > 0 AND clock_in IS NOT NULL AND date IS NOT NULL THEN 
            CONCAT(date, ' ', clock_in)
        WHEN @has_clock_in > 0 AND clock_in IS NOT NULL THEN 
            CONCAT(COALESCE(date, CURDATE()), ' ', clock_in)
        WHEN @has_check_in > 0 AND check_in IS NOT NULL THEN 
            check_in
        ELSE 
            COALESCE(created_at, NOW())
    END as check_in,
    CASE 
        WHEN @has_clock_in > 0 AND @has_date > 0 AND clock_out IS NOT NULL AND clock_out != '00:00:00' AND date IS NOT NULL THEN 
            CONCAT(date, ' ', clock_out)
        WHEN @has_clock_in > 0 AND clock_out IS NOT NULL AND clock_out != '00:00:00' THEN 
            CONCAT(COALESCE(date, CURDATE()), ' ', clock_out)
        WHEN @has_check_in > 0 AND check_out IS NOT NULL AND check_out != '0000-00-00 00:00:00' THEN 
            check_out
        ELSE 
            NULL
    END as check_out,
    COALESCE(status, 'present') as status,
    COALESCE(created_at, NOW()) as created_at
FROM attendance_backup_temp
WHERE user_id IS NOT NULL;

-- Create attendance_rules table if it doesn't exist
CREATE TABLE IF NOT EXISTS attendance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_latitude DECIMAL(10, 8) DEFAULT 0,
    office_longitude DECIMAL(11, 8) DEFAULT 0,
    office_radius_meters INT DEFAULT 200,
    is_gps_required BOOLEAN DEFAULT TRUE,
    grace_period_minutes INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default attendance rules
INSERT IGNORE INTO attendance_rules (office_latitude, office_longitude, office_radius_meters, is_gps_required, grace_period_minutes)
VALUES (0, 0, 200, TRUE, 15);

-- Create shifts table if it doesn't exist
CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    grace_period INT DEFAULT 15,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default shift
INSERT IGNORE INTO shifts (id, name, start_time, end_time, grace_period)
VALUES (1, 'Regular Shift', '09:00:00', '18:00:00', 15);

-- Add shift_id to users table if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='users' AND column_name='shift_id' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE users ADD COLUMN shift_id INT DEFAULT 1',
    'SELECT "shift_id column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create attendance_corrections table for correction requests
CREATE TABLE IF NOT EXISTS attendance_corrections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    correction_date DATE NOT NULL,
    requested_check_in DATETIME NULL,
    requested_check_out DATETIME NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Keep backup table for safety (can be dropped manually later)
-- DROP TABLE IF EXISTS attendance_backup_temp;

-- Verify the fix
SELECT 'Attendance table structure fixed successfully' as message;
SELECT COUNT(*) as total_attendance_records FROM attendance;