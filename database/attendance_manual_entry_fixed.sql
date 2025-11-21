-- Manual Attendance Entry System Database Schema

-- Add columns one by one (MySQL doesn't support IF NOT EXISTS for columns)
ALTER TABLE attendance ADD COLUMN entry_type ENUM('automatic', 'manual') DEFAULT 'automatic';
ALTER TABLE attendance ADD COLUMN reason VARCHAR(100) NULL;
ALTER TABLE attendance ADD COLUMN notes TEXT NULL;
ALTER TABLE attendance ADD COLUMN created_by INT NULL;
ALTER TABLE attendance ADD COLUMN updated_by INT NULL;
ALTER TABLE attendance ADD COLUMN manual_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE attendance ADD COLUMN manual_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create attendance logs table for audit trail
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Create index for manual entries
CREATE INDEX idx_attendance_manual ON attendance(entry_type, user_id, manual_created_at);