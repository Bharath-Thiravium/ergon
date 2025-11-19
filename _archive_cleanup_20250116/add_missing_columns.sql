-- Add missing columns to daily_tasks table
-- Run this in phpMyAdmin or MySQL command line

USE ergon;

-- Add time tracking columns
ALTER TABLE daily_tasks ADD COLUMN start_time TIMESTAMP NULL;
ALTER TABLE daily_tasks ADD COLUMN pause_time TIMESTAMP NULL;
ALTER TABLE daily_tasks ADD COLUMN resume_time TIMESTAMP NULL;
ALTER TABLE daily_tasks ADD COLUMN completion_time TIMESTAMP NULL;
ALTER TABLE daily_tasks ADD COLUMN active_seconds INT DEFAULT 0;
ALTER TABLE daily_tasks ADD COLUMN completed_percentage INT DEFAULT 0;
ALTER TABLE daily_tasks ADD COLUMN postponed_from_date DATE NULL;

-- Verify the structure
DESCRIBE daily_tasks;