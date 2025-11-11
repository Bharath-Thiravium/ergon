-- Consolidated Migration for ERGON Employee Tracker
-- Works with both localhost and Hostinger environments
-- Ensures task categories are properly set based on department

-- Add required columns to tasks table if they don't exist
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

-- Create daily_planner table if it doesn't exist
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_date (user_id, date),
    KEY idx_status (completion_status)
);

-- Create evening_updates table if it doesn't exist
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_date (user_id, planner_date)
);

-- Create task_categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dept_category (department_name, category_name),
    KEY idx_department (department_name)
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
('Sales', 'Proposal', 'Proposal and contract management'),
('IT', 'Development', 'Software development and coding tasks'),
('IT', 'Testing', 'Quality assurance and testing activities'),
('IT', 'Bug Fixing', 'Error resolution and debugging'),
('Accounting', 'Ledger Update', 'General ledger maintenance and updates'),
('Accounting', 'Invoice Creation', 'Customer invoice generation'),
('Accounting', 'Payment Follow-up', 'Outstanding payment collection');

-- Update existing data with safe defaults
UPDATE tasks SET assigned_for = 'self' WHERE assigned_for IS NULL;
UPDATE tasks SET followup_required = FALSE WHERE followup_required IS NULL;
UPDATE tasks SET progress = 0 WHERE progress IS NULL;
UPDATE tasks SET sla_hours = 24 WHERE sla_hours IS NULL OR sla_hours = 0;

-- Update task_category based on department (with collation handling)
UPDATE tasks t 
LEFT JOIN users u ON t.assigned_to = u.id 
LEFT JOIN departments d ON u.department_id = d.id 
SET t.task_category = (
    SELECT tc.category_name 
    FROM task_categories tc 
    WHERE tc.department_name = d.name 
       OR tc.department_name = CASE 
           WHEN d.name LIKE '%IT%' OR d.name LIKE '%Information%' THEN 'IT'
           WHEN d.name LIKE '%Finance%' OR d.name LIKE '%Account%' THEN 'Accounting'
           WHEN d.name LIKE '%HR%' OR d.name LIKE '%Human%' THEN 'Human Resources'
           WHEN d.name LIKE '%Marketing%' OR d.name LIKE '%Sales%' THEN 'Marketing'
           ELSE d.name
       END
    LIMIT 1
)
WHERE t.task_category IS NULL AND d.name IS NOT NULL;

-- Set default category for tasks without department mapping
UPDATE tasks SET task_category = 'General' WHERE task_category IS NULL;