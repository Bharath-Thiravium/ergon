-- Employee Panel Improvements Migration
-- Run once to apply schema changes

-- 1. Site Reports: add submission_timing column (on_time / late)
ALTER TABLE site_reports
    ADD COLUMN IF NOT EXISTS submission_timing ENUM('on_time','late') DEFAULT 'on_time' AFTER status;

-- 2. Projects: add project_type column for geo-fence radius logic
ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS project_type VARCHAR(50) DEFAULT 'office' AFTER status;
-- Values: 'office' (150m default), 'site'/'field'/'construction'/'outdoor' (400m default)

-- 3. Settings: increase default attendance_radius from 5 to 150 meters
UPDATE settings SET attendance_radius = 150 WHERE attendance_radius <= 10;

-- 4. Projects: ensure checkin_radius has a sensible default (150m)
UPDATE projects SET checkin_radius = 150 WHERE checkin_radius IS NULL OR checkin_radius = 0;
