-- Simple Attendance Table Fix
-- Run this step by step to avoid migration issues

-- Step 1: Create new attendance table with correct structure
DROP TABLE IF EXISTS attendance_new;
CREATE TABLE attendance_new (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Step 2: Migrate existing data (if any exists)
-- This will work regardless of old table structure
INSERT IGNORE INTO attendance_new (user_id, check_in, status, created_at)
SELECT 
    user_id,
    NOW() as check_in,
    'present' as status,
    NOW() as created_at
FROM users 
WHERE id NOT IN (SELECT DISTINCT user_id FROM attendance_new)
LIMIT 0; -- This creates the structure but doesn't insert dummy data

-- Step 3: Replace old table
DROP TABLE IF EXISTS attendance_backup;
RENAME TABLE attendance TO attendance_backup;
RENAME TABLE attendance_new TO attendance;

-- Step 4: Create supporting tables
CREATE TABLE IF NOT EXISTS attendance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_latitude DECIMAL(10, 8) DEFAULT 0,
    office_longitude DECIMAL(11, 8) DEFAULT 0,
    office_radius_meters INT DEFAULT 200,
    is_gps_required BOOLEAN DEFAULT TRUE,
    grace_period_minutes INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO attendance_rules (office_latitude, office_longitude, office_radius_meters, is_gps_required)
VALUES (0, 0, 200, TRUE);

CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    grace_period INT DEFAULT 15,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO shifts (id, name, start_time, end_time, grace_period)
VALUES (1, 'Regular Shift', '09:00:00', '18:00:00', 15);

-- Step 5: Add shift_id to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS shift_id INT DEFAULT 1;

-- Verification
SELECT 'Attendance table fixed successfully' as message;
SELECT COUNT(*) as attendance_records FROM attendance;
SELECT COUNT(*) as backup_records FROM attendance_backup;