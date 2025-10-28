-- Settings table migration for Google Maps integration
-- Add location-related columns to settings table

ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS office_latitude DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS office_longitude DECIMAL(11, 8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS office_address TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS attendance_radius INT DEFAULT 200;

-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) DEFAULT 'ERGON',
    company_email VARCHAR(255) DEFAULT '',
    company_phone VARCHAR(50) DEFAULT '',
    company_address TEXT DEFAULT '',
    working_hours_start TIME DEFAULT '09:00:00',
    working_hours_end TIME DEFAULT '18:00:00',
    timezone VARCHAR(100) DEFAULT 'UTC',
    office_latitude DECIMAL(10, 8) DEFAULT NULL,
    office_longitude DECIMAL(11, 8) DEFAULT NULL,
    office_address TEXT DEFAULT NULL,
    attendance_radius INT DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Ensure employee_id column exists in users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS employee_id VARCHAR(20) UNIQUE DEFAULT NULL;

-- Create index for employee_id for better performance
CREATE INDEX IF NOT EXISTS idx_employee_id ON users(employee_id);