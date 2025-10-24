-- Fix tasks table structure and add missing column
ALTER TABLE tasks ADD COLUMN due_date DATE NULL AFTER status;
ALTER TABLE tasks ADD INDEX idx_tasks_due_date (due_date);