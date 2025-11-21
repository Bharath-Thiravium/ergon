-- Manual Attendance Entry System Database Schema - Safe Version

-- Only add columns that don't exist
-- Check existing columns first: SHOW COLUMNS FROM attendance;

-- Add only missing columns (skip if already exists)
-- ALTER TABLE attendance ADD COLUMN entry_type ENUM('automatic', 'manual') DEFAULT 'automatic';
-- ALTER TABLE attendance ADD COLUMN reason VARCHAR(100) NULL;
-- ALTER TABLE attendance ADD COLUMN notes TEXT NULL;
-- ALTER TABLE attendance ADD COLUMN created_by INT NULL;
-- ALTER TABLE attendance ADD COLUMN updated_by INT NULL;

-- Create attendance logs table for audit trail
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action)
);

-- Update API to handle existing columns
-- The manual attendance system will work with existing table structure