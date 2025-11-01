-- Safe settings table fix - won't fail if table exists
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS working_hours_end TIME DEFAULT '18:00:00',
ADD COLUMN IF NOT EXISTS office_address TEXT DEFAULT '';

-- Insert default record only if none exists
INSERT IGNORE INTO settings (id, company_name, timezone, working_hours_start, working_hours_end, attendance_radius) 
VALUES (1, 'ERGON Company', 'Asia/Kolkata', '09:00:00', '18:00:00', 200);

-- Update existing records with safe defaults
UPDATE settings 
SET 
    working_hours_start = COALESCE(working_hours_start, '09:00:00'),
    working_hours_end = COALESCE(working_hours_end, '18:00:00'),
    timezone = COALESCE(timezone, 'Asia/Kolkata'),
    attendance_radius = COALESCE(attendance_radius, 200)
WHERE id = 1;