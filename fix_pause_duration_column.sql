-- Add missing pause_duration column to daily_tasks table
USE ergon_db;

ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0 AFTER active_seconds;

-- Verify the column was added
DESCRIBE daily_tasks;