-- Create daily_planner table
CREATE TABLE IF NOT EXISTS daily_planner (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NULL,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    planned_start_time TIME NULL,
    planned_duration INT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    priority_order INT DEFAULT 1,
    completion_status ENUM('not_started', 'in_progress', 'completed', 'postponed') DEFAULT 'not_started',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- Update tasks table to add missing columns
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS assigned_by INT,
ADD COLUMN IF NOT EXISTS assigned_for VARCHAR(50) DEFAULT 'task',
ADD COLUMN IF NOT EXISTS planned_date DATE,
ADD COLUMN IF NOT EXISTS deadline DATE,
ADD COLUMN IF NOT EXISTS progress INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS task_category VARCHAR(100),
ADD COLUMN IF NOT EXISTS followup_required BOOLEAN DEFAULT FALSE,
MODIFY COLUMN status ENUM('assigned', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'assigned';

-- Create evening_updates table if not exists
CREATE TABLE IF NOT EXISTS evening_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT 'Daily Update',
    accomplishments TEXT,
    challenges TEXT,
    tomorrow_plan TEXT,
    overall_productivity INT DEFAULT 0,
    planner_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, planner_date)
);