-- Fix settings table structure for ERGON system
-- Run this to fix the settings form error

-- Ensure settings table exists with correct structure
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'ERGON Company',
    company_email VARCHAR(255) DEFAULT '',
    company_phone VARCHAR(20) DEFAULT '',
    company_address TEXT DEFAULT '',
    working_hours_start TIME DEFAULT '09:00:00',
    working_hours_end TIME DEFAULT '18:00:00',
    timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
    base_location_lat DECIMAL(10,8) DEFAULT 0,
    base_location_lng DECIMAL(11,8) DEFAULT 0,
    attendance_radius INT DEFAULT 200,
    office_address TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add missing columns if they don't exist
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS working_hours_end TIME DEFAULT '18:00:00';

-- Insert default record if none exists
INSERT IGNORE INTO settings (id, company_name, timezone, working_hours_start, working_hours_end, attendance_radius) 
VALUES (1, 'ERGON Company', 'Asia/Kolkata', '09:00:00', '18:00:00', 200);

-- Update any existing records to have proper default values
UPDATE settings 
SET 
    working_hours_start = COALESCE(working_hours_start, '09:00:00'),
    working_hours_end = COALESCE(working_hours_end, '18:00:00'),
    timezone = COALESCE(timezone, 'Asia/Kolkata'),
    attendance_radius = COALESCE(attendance_radius, 200)
WHERE id = 1;

COMMIT;