-- Minimal Attendance Fix - Just create correct structure
-- Run this to fix attendance table without data migration

-- Backup existing table
DROP TABLE IF EXISTS attendance_old;
RENAME TABLE attendance TO attendance_old;

-- Create new attendance table with correct structure
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

-- Create supporting tables
CREATE TABLE IF NOT EXISTS attendance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_latitude DECIMAL(10, 8) DEFAULT 0,
    office_longitude DECIMAL(11, 8) DEFAULT 0,
    office_radius_meters INT DEFAULT 200,
    is_gps_required BOOLEAN DEFAULT TRUE,
    grace_period_minutes INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO attendance_rules VALUES (1, 0, 0, 200, TRUE, 15, NOW());

CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    grace_period INT DEFAULT 15,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO shifts VALUES (1, 'Regular Shift', '09:00:00', '18:00:00', 15, TRUE, NOW());

-- Verify
SELECT 'Attendance table fixed - ready for use' as message;