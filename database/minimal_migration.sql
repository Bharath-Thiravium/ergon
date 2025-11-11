-- Minimal Unified Workflow Migration
-- Only essential changes for unified workflow

-- Add required columns to tasks table
ALTER TABLE tasks ADD COLUMN assigned_for ENUM('self','other') DEFAULT 'self';
ALTER TABLE tasks ADD COLUMN followup_required BOOLEAN DEFAULT FALSE;
ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN progress INT DEFAULT 0;
ALTER TABLE tasks ADD COLUMN sla_hours INT DEFAULT 24;

-- Create daily_planner table
CREATE TABLE daily_planner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT DEFAULT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    planned_start_time TIME DEFAULT NULL,
    completion_status ENUM('not_started','in_progress','completed','postponed') DEFAULT 'not_started',
    notes TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'planned',
    priority_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create evening_updates table
CREATE TABLE evening_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT 'Daily Update',
    accomplishments TEXT DEFAULT NULL,
    challenges TEXT DEFAULT NULL,
    tomorrow_plan TEXT DEFAULT NULL,
    overall_productivity INT DEFAULT 0,
    planner_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Update existing data
UPDATE tasks SET assigned_for = 'self' WHERE assigned_for IS NULL;
UPDATE tasks SET followup_required = FALSE WHERE followup_required IS NULL;
UPDATE tasks SET progress = 0 WHERE progress IS NULL;
UPDATE tasks SET sla_hours = 24 WHERE sla_hours IS NULL OR sla_hours = 0;
UPDATE tasks SET task_category = 'General' WHERE task_category IS NULL;