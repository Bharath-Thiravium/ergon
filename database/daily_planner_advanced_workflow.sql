-- Advanced Daily Planner Workflow Migration
-- Adds time tracking, SLA calculation, and enhanced task management

-- Create daily_tasks table (enhanced daily planner)
CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NULL,
    scheduled_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    planned_start_time TIME NULL,
    planned_duration INT NULL COMMENT 'Planned duration in minutes',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('not_started', 'in_progress', 'paused', 'completed', 'postponed') DEFAULT 'not_started',
    start_time DATETIME NULL,
    pause_time DATETIME NULL,
    resume_time DATETIME NULL,
    completion_time DATETIME NULL,
    active_seconds INT DEFAULT 0 COMMENT 'Total active working time in seconds',
    completed_percentage INT DEFAULT 0,
    postponed_from_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, scheduled_date),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date)
);

-- Enhance tasks table for workflow integration
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS actual_time_seconds INT DEFAULT 0 COMMENT 'Total time spent on task',
ADD COLUMN IF NOT EXISTS completed_percentage INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS workflow_status ENUM('not_started', 'in_progress', 'paused', 'completed', 'postponed') DEFAULT 'not_started',
ADD COLUMN IF NOT EXISTS sla_minutes INT NULL COMMENT 'SLA time in minutes',
ADD COLUMN IF NOT EXISTS estimated_duration INT NULL COMMENT 'Estimated duration in minutes';

-- Create time_logs table for detailed time tracking
CREATE TABLE IF NOT EXISTS time_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    daily_task_id INT NOT NULL,
    task_id INT NULL,
    user_id INT NOT NULL,
    action ENUM('start', 'pause', 'resume', 'complete', 'postpone') NOT NULL,
    timestamp DATETIME NOT NULL,
    active_duration INT DEFAULT 0 COMMENT 'Duration of this session in seconds',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (daily_task_id) REFERENCES daily_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_daily_task (daily_task_id),
    INDEX idx_user_date (user_id, timestamp)
);

-- Create daily_performance table for SLA and metrics tracking
CREATE TABLE IF NOT EXISTS daily_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    total_planned_minutes INT DEFAULT 0,
    total_active_minutes INT DEFAULT 0,
    total_tasks INT DEFAULT 0,
    completed_tasks INT DEFAULT 0,
    in_progress_tasks INT DEFAULT 0,
    postponed_tasks INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    sla_adherence_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
);

-- Migrate existing daily_planner data to daily_tasks
INSERT INTO daily_tasks (
    user_id, task_id, scheduled_date, title, description, 
    planned_start_time, planned_duration, priority, status, 
    completed_percentage, notes, created_at, updated_at
)
SELECT 
    user_id, task_id, date, title, description,
    planned_start_time, planned_duration, priority,
    CASE completion_status
        WHEN 'not_started' THEN 'not_started'
        WHEN 'in_progress' THEN 'in_progress'
        WHEN 'completed' THEN 'completed'
        WHEN 'postponed' THEN 'postponed'
        ELSE 'not_started'
    END as status,
    0 as completed_percentage,
    notes, created_at, updated_at
FROM daily_planner
WHERE NOT EXISTS (
    SELECT 1 FROM daily_tasks dt 
    WHERE dt.user_id = daily_planner.user_id 
    AND dt.scheduled_date = daily_planner.date 
    AND dt.title = daily_planner.title
);