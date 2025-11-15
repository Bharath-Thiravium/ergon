-- QUICK FIX: Run these commands in phpMyAdmin

-- 1. Drop old backup and recreate attendance table
DROP TABLE IF EXISTS attendance_old;
RENAME TABLE attendance TO attendance_old;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    location_name VARCHAR(255) DEFAULT 'Office',
    status VARCHAR(20) DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Create attendance rules
CREATE TABLE IF NOT EXISTS attendance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_latitude DECIMAL(10, 8) DEFAULT 0,
    office_longitude DECIMAL(11, 8) DEFAULT 0,
    office_radius_meters INT DEFAULT 200,
    is_gps_required BOOLEAN DEFAULT TRUE
);
INSERT IGNORE INTO attendance_rules (office_latitude, office_longitude, office_radius_meters, is_gps_required) VALUES (0, 0, 200, TRUE);

-- 3. Done! Test attendance at /ergon/attendance/clock