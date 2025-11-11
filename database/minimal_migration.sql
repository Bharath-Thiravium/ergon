-- Minimal Unified Workflow Migration
-- Only essential changes for unified workflow

-- Add required columns to tasks table (skip if already exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='assigned_for' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN assigned_for ENUM("self","other") DEFAULT "self"',
    'SELECT "assigned_for column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='followup_required' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN followup_required BOOLEAN DEFAULT FALSE',
    'SELECT "followup_required column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='planned_date' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL',
    'SELECT "planned_date column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='task_category' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL',
    'SELECT "task_category column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='progress' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN progress INT DEFAULT 0',
    'SELECT "progress column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='sla_hours' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN sla_hours INT DEFAULT 24',
    'SELECT "sla_hours column already exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create daily_planner table
CREATE TABLE IF NOT EXISTS daily_planner (
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
CREATE TABLE IF NOT EXISTS evening_updates (
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

-- Create task_categories table for department-based categories
CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dept_category (department_name, category_name)
);

-- Insert default categories for common departments
INSERT IGNORE INTO task_categories (department_name, category_name, description) VALUES
('Human Resources', 'Recruitment', 'Hiring and onboarding tasks'),
('Human Resources', 'Employee Relations', 'Employee management and relations'),
('Human Resources', 'Training', 'Training and development activities'),
('Information Technology', 'Development', 'Software development tasks'),
('Information Technology', 'Support', 'Technical support and maintenance'),
('Information Technology', 'Infrastructure', 'System and infrastructure management'),
('Finance', 'Accounting', 'Financial accounting and bookkeeping'),
('Finance', 'Budgeting', 'Budget planning and analysis'),
('Finance', 'Audit', 'Financial auditing tasks'),
('Marketing', 'Campaign', 'Marketing campaign activities'),
('Marketing', 'Content', 'Content creation and management'),
('Marketing', 'Analytics', 'Marketing analytics and reporting'),
('Operations', 'Process', 'Operational process management'),
('Operations', 'Quality', 'Quality assurance and control'),
('Operations', 'Logistics', 'Supply chain and logistics'),
('Sales', 'Lead Generation', 'Lead generation activities'),
('Sales', 'Client Follow-up', 'Client follow-up and relationship management'),
('Sales', 'Proposal', 'Proposal and contract management');

-- Update existing data
UPDATE tasks SET assigned_for = 'self' WHERE assigned_for IS NULL;
UPDATE tasks SET followup_required = FALSE WHERE followup_required IS NULL;
UPDATE tasks SET progress = 0 WHERE progress IS NULL;
UPDATE tasks SET sla_hours = 24 WHERE sla_hours IS NULL OR sla_hours = 0;

-- Update task_category based on department
UPDATE tasks t 
JOIN users u ON t.assigned_to = u.id 
JOIN departments d ON u.department_id = d.id 
SET t.task_category = (
    SELECT tc.category_name 
    FROM task_categories tc 
    WHERE CONVERT(tc.department_name USING utf8mb4) COLLATE utf8mb4_0900_ai_ci = CONVERT(d.name USING utf8mb4) COLLATE utf8mb4_0900_ai_ci
    LIMIT 1
)
WHERE t.task_category IS NULL;

-- Set default category for tasks without department mapping
UPDATE tasks SET task_category = 'General' WHERE task_category IS NULL;