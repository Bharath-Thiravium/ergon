-- SLA Timer Enhancement Database Schema
-- Adds necessary columns for proper SLA timer tracking

-- Add missing columns to daily_tasks table if they don't exist
ALTER TABLE daily_tasks 
ADD COLUMN IF NOT EXISTS active_seconds INT DEFAULT 0 COMMENT 'Total active working seconds',
ADD COLUMN IF NOT EXISTS pause_duration INT DEFAULT 0 COMMENT 'Total pause duration in seconds',
ADD COLUMN IF NOT EXISTS total_pause_duration INT DEFAULT 0 COMMENT 'Cumulative pause time',
ADD COLUMN IF NOT EXISTS remaining_sla_time INT DEFAULT 0 COMMENT 'Remaining SLA time when paused',
ADD COLUMN IF NOT EXISTS overdue_start_time TIMESTAMP NULL COMMENT 'When task became overdue',
ADD COLUMN IF NOT EXISTS time_used INT DEFAULT 0 COMMENT 'Total time used including overdue',
ADD COLUMN IF NOT EXISTS sla_end_time TIMESTAMP NULL COMMENT 'When SLA expires',
ADD COLUMN IF NOT EXISTS pause_start_time TIMESTAMP NULL COMMENT 'When current pause started',
ADD COLUMN IF NOT EXISTS resume_time TIMESTAMP NULL COMMENT 'When task was last resumed';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_daily_tasks_sla_tracking ON daily_tasks (status, start_time, sla_end_time);
CREATE INDEX IF NOT EXISTS idx_daily_tasks_pause_tracking ON daily_tasks (status, pause_start_time);
CREATE INDEX IF NOT EXISTS idx_daily_tasks_active_time ON daily_tasks (active_seconds, pause_duration);

-- Create SLA timer history table for audit trail
CREATE TABLE IF NOT EXISTS sla_timer_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active_seconds_before INT DEFAULT 0,
    active_seconds_after INT DEFAULT 0,
    pause_duration_before INT DEFAULT 0,
    pause_duration_after INT DEFAULT 0,
    remaining_sla_time INT DEFAULT 0,
    notes TEXT,
    INDEX idx_task_history (daily_task_id, timestamp),
    INDEX idx_user_history (user_id, timestamp),
    INDEX idx_action_history (action, timestamp),
    FOREIGN KEY (daily_task_id) REFERENCES daily_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create daily task history table if it doesn't exist
CREATE TABLE IF NOT EXISTS daily_task_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_task_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_daily_task_history (daily_task_id, created_at),
    INDEX idx_daily_task_action (action, created_at),
    FOREIGN KEY (daily_task_id) REFERENCES daily_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing tasks to have proper SLA defaults
UPDATE daily_tasks 
SET active_seconds = COALESCE(active_seconds, 0),
    pause_duration = COALESCE(pause_duration, 0),
    total_pause_duration = COALESCE(total_pause_duration, 0),
    remaining_sla_time = COALESCE(remaining_sla_time, 0),
    time_used = COALESCE(time_used, 0)
WHERE active_seconds IS NULL 
   OR pause_duration IS NULL 
   OR total_pause_duration IS NULL 
   OR remaining_sla_time IS NULL 
   OR time_used IS NULL;

-- Add SLA hours to tasks table if not exists
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS sla_hours DECIMAL(4,2) DEFAULT 0.25 COMMENT 'SLA time in hours (default 15 minutes)';

-- Update existing tasks to have default SLA
UPDATE tasks 
SET sla_hours = 0.25 
WHERE sla_hours IS NULL OR sla_hours = 0;