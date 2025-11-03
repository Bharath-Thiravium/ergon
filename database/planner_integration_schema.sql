-- Daily Planner and Evening Update Integration Schema
-- Run this to create the integrated task management system

-- Create daily_planner table
CREATE TABLE IF NOT EXISTS daily_planner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    task_id INT NULL,
    task_type ENUM('assigned', 'personal') DEFAULT 'assigned',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    planned_start_time TIME,
    planned_duration INT DEFAULT 60,
    priority_order INT DEFAULT 1,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, date)
);

-- Create evening_updates table
CREATE TABLE IF NOT EXISTS evening_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    planner_id INT NULL,
    task_id INT NULL,
    progress_percentage INT DEFAULT 0,
    actual_hours_spent DECIMAL(4,2) DEFAULT 0,
    completion_status ENUM('not_started', 'in_progress', 'completed', 'blocked') DEFAULT 'not_started',
    blockers TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (planner_id) REFERENCES daily_planner(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, date)
);

-- Extend tasks table with progress tracking (run individually if columns exist)
-- ALTER TABLE tasks ADD COLUMN overall_progress INT DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN total_time_spent DECIMAL(6,2) DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN estimated_hours DECIMAL(4,2) DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN last_progress_update TIMESTAMP NULL;

-- Create indexes for better performance
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_deadline ON tasks(deadline);