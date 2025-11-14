-- Final fix for attendance table to ensure proper column structure
-- This handles both TIME and DATETIME column scenarios

-- First, let's standardize the attendance table structure
DROP TABLE IF EXISTS attendance_backup;
CREATE TABLE attendance_backup AS SELECT * FROM attendance;

-- Recreate attendance table with proper structure
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_check_in_date (check_in),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Migrate data from backup if it exists
INSERT IGNORE INTO attendance (user_id, check_in, check_out, status, created_at)
SELECT 
    user_id,
    CASE 
        WHEN clock_in IS NOT NULL AND date IS NOT NULL THEN CONCAT(date, ' ', clock_in)
        WHEN check_in IS NOT NULL THEN check_in
        ELSE created_at
    END as check_in,
    CASE 
        WHEN clock_out IS NOT NULL AND clock_out != '00:00:00' AND date IS NOT NULL THEN CONCAT(date, ' ', clock_out)
        WHEN check_out IS NOT NULL AND check_out != '0000-00-00 00:00:00' AND check_out != '' THEN check_out
        ELSE NULL
    END as check_out,
    COALESCE(status, 'present') as status,
    created_at
FROM attendance_backup
WHERE user_id IS NOT NULL;