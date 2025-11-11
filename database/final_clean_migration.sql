-- Final Clean Migration - 14 Essential Tables Only
-- Migrates data and removes unused tables

-- Step 1: Add missing columns to tasks table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='assigned_for' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN assigned_for ENUM("self","other") DEFAULT "self"',
    'SELECT "assigned_for exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='followup_required' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN followup_required BOOLEAN DEFAULT FALSE',
    'SELECT "followup_required exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='planned_date' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL',
    'SELECT "planned_date exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='task_category' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL',
    'SELECT "task_category exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='progress' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN progress INT DEFAULT 0',
    'SELECT "progress exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name='tasks' AND column_name='sla_hours' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE tasks ADD COLUMN sla_hours INT DEFAULT 24',
    'SELECT "sla_hours exists"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Step 2: Create new unified daily_planner table
CREATE TABLE IF NOT EXISTS daily_planner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT DEFAULT NULL,
    plan_date DATE NOT NULL,
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

-- Step 3: Skip data migration - daily_plans table doesn't exist in current schema

-- Step 4: Create other essential tables
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

CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dept_category (department_name, category_name)
);

-- Step 5: Insert comprehensive task categories from original system
INSERT IGNORE INTO task_categories (department_name, category_name, description) VALUES
-- Liaison Department Categories
('Liaison', 'Document Collection', 'Gathering required documents from clients'),
('Liaison', 'Portal Upload', 'Uploading details in government portals'),
('Liaison', 'Documentation', 'Document preparation and verification'),
('Liaison', 'Follow-up', 'Client and government office follow-ups'),
('Liaison', 'Document Submission', 'Physical document submission'),
('Liaison', 'Courier Services', 'Document dispatch and delivery'),
('Liaison', 'Client Meeting', 'Client consultation and meetings'),
('Liaison', 'Government Office Visit', 'Official visits and submissions'),
-- Human Resources Categories
('Human Resources', 'Recruitment', 'Hiring and recruitment activities'),
('Human Resources', 'Training', 'Employee training and development'),
('Human Resources', 'Performance Review', 'Employee performance evaluations'),
('Human Resources', 'Policy Development', 'HR policy creation and updates'),
('Human Resources', 'Employee Relations', 'Managing employee relations and issues'),
('Human Resources', 'Compliance', 'HR compliance and regulatory tasks'),
-- Operations Categories
('Operations', 'Process Improvement', 'Improving operational processes'),
('Operations', 'Quality Control', 'Quality assurance and control'),
('Operations', 'Vendor Management', 'Managing vendor relationships'),
('Operations', 'Inventory Management', 'Managing inventory and supplies'),
('Operations', 'Logistics', 'Logistics and supply chain management'),
('Operations', 'Facility Management', 'Managing office facilities'),
-- Finance & Accounts Categories
('Finance & Accounts', 'Ledger Update', 'General ledger maintenance and updates'),
('Finance & Accounts', 'Invoice Creation', 'Customer invoice generation'),
('Finance & Accounts', 'Quotation Creation', 'Price quotation preparation'),
('Finance & Accounts', 'PO Creation', 'Purchase order generation'),
('Finance & Accounts', 'PO Follow-up', 'Purchase order tracking and follow-up'),
('Finance & Accounts', 'Payment Follow-up', 'Outstanding payment collection'),
('Finance & Accounts', 'Ledger Follow-up', 'Account reconciliation and follow-up'),
('Finance & Accounts', 'GST Follow-up', 'GST compliance and filing'),
('Finance & Accounts', 'Mail Checking', 'Email correspondence and communication'),
('Finance & Accounts', 'Financial Reporting', 'Monthly and quarterly reports'),
('Finance & Accounts', 'Accounting', 'General accounting and bookkeeping'),
('Finance & Accounts', 'Budgeting', 'Budget planning and management'),
('Finance & Accounts', 'Financial Analysis', 'Financial data analysis and reporting'),
('Finance & Accounts', 'Audit', 'Internal and external audit activities'),
('Finance & Accounts', 'Tax Planning', 'Tax preparation and planning'),
('Finance & Accounts', 'Invoice Processing', 'Processing invoices and payments'),
('Finance & Accounts', 'Bank Reconciliation', 'Bank account reconciliation'),
('Finance & Accounts', 'Expense Tracking', 'Expense monitoring and analysis'),
('Finance & Accounts', 'Petty Cash Management', 'Petty cash handling'),
('Finance & Accounts', 'Vendor Payment', 'Vendor payment processing'),
('Finance & Accounts', 'Customer Payment Processing', 'Customer payment handling'),
('Finance & Accounts', 'Cash Flow Management', 'Cash flow planning and monitoring'),
('Finance & Accounts', 'Investment Analysis', 'Investment evaluation and analysis'),
('Finance & Accounts', 'Cost Analysis', 'Cost evaluation and optimization'),
('Finance & Accounts', 'GST Filing', 'GST return preparation and filing'),
('Finance & Accounts', 'TDS Processing', 'TDS calculation and filing'),
('Finance & Accounts', 'Loan Management', 'Loan processing and management'),
('Finance & Accounts', 'Asset Management', 'Asset tracking and management'),
-- Information Technology Categories
('Information Technology', 'Development', 'Software development and coding tasks'),
('Information Technology', 'Testing', 'Quality assurance and testing activities'),
('Information Technology', 'Bug Fixing', 'Error resolution and debugging'),
('Information Technology', 'Planning', 'Project planning and architecture'),
('Information Technology', 'Hosting', 'Server management and deployment'),
('Information Technology', 'Maintenance', 'System maintenance and updates'),
('Information Technology', 'Documentation', 'Technical documentation and guides'),
('Information Technology', 'Code Review', 'Peer code review and quality checks'),
('Information Technology', 'Deployment', 'Application deployment and release'),
('Information Technology', 'System Analysis', 'System analysis and design'),
('Information Technology', 'Database Design', 'Database design and optimization'),
('Information Technology', 'API Development', 'API development and integration'),
('Information Technology', 'Frontend Development', 'Frontend development tasks'),
('Information Technology', 'Backend Development', 'Backend development tasks'),
('Information Technology', 'DevOps', 'DevOps and automation tasks'),
('Information Technology', 'Cloud Management', 'Cloud infrastructure management'),
('Information Technology', 'Security Implementation', 'Security implementation and monitoring'),
('Information Technology', 'System Administration', 'System administration tasks'),
('Information Technology', 'Database Management', 'Database administration'),
('Information Technology', 'Security Updates', 'Security patches and updates'),
('Information Technology', 'Backup Management', 'Data backup and recovery'),
('Information Technology', 'Network Management', 'Network administration'),
('Information Technology', 'User Support', 'Technical user support'),
('Information Technology', 'Software Installation', 'Software installation and configuration'),
('Information Technology', 'Hardware Maintenance', 'Hardware maintenance and repair'),
('Information Technology', 'Performance Monitoring', 'System performance monitoring'),
-- Marketing & Sales Categories
('Marketing & Sales', 'Campaign Planning', 'Marketing campaign strategy and planning'),
('Marketing & Sales', 'Content Creation', 'Marketing content and material creation'),
('Marketing & Sales', 'Social Media Management', 'Social media posts and engagement'),
('Marketing & Sales', 'Lead Generation', 'Prospecting and lead identification'),
('Marketing & Sales', 'Client Presentation', 'Sales presentations and proposals'),
('Marketing & Sales', 'Market Research', 'Industry and competitor analysis'),
('Marketing & Sales', 'Event Planning', 'Marketing events and webinars'),
('Marketing & Sales', 'Email Marketing', 'Email campaigns and newsletters'),
('Marketing & Sales', 'Client Meeting', 'Meeting with clients and prospects'),
('Marketing & Sales', 'Proposal Writing', 'Creating sales proposals and quotes'),
('Marketing & Sales', 'Customer Support', 'Supporting existing customers'),
('Marketing & Sales', 'Brand Management', 'Brand development and management'),
('Marketing & Sales', 'Digital Marketing', 'Digital marketing campaigns'),
('Marketing & Sales', 'SEO/SEM', 'Search engine optimization and marketing'),
('Marketing & Sales', 'Public Relations', 'Public relations and communications'),
('Marketing & Sales', 'Customer Surveys', 'Customer feedback and surveys'),
('Marketing & Sales', 'Competitor Analysis', 'Competitive analysis and research'),
('Marketing & Sales', 'Product Promotion', 'Product promotion and marketing'),
('Marketing & Sales', 'Sales Presentation', 'Sales presentation development'),
('Marketing & Sales', 'Deal Negotiation', 'Deal negotiation and closing'),
('Marketing & Sales', 'Customer Onboarding', 'New customer onboarding'),
('Marketing & Sales', 'Account Management', 'Existing account management'),
('Marketing & Sales', 'Sales Reporting', 'Sales performance reporting'),
('Marketing & Sales', 'CRM Management', 'CRM system management'),
('Marketing & Sales', 'Territory Management', 'Sales territory management'),
('Marketing & Sales', 'Product Demo', 'Product demonstration'),
('Marketing & Sales', 'Contract Management', 'Contract negotiation and management');

-- Step 6: Update existing data
UPDATE tasks SET assigned_for = 'self' WHERE assigned_for IS NULL;
UPDATE tasks SET followup_required = FALSE WHERE followup_required IS NULL;
UPDATE tasks SET progress = 0 WHERE progress IS NULL;
UPDATE tasks SET sla_hours = 24 WHERE sla_hours IS NULL OR sla_hours = 0;

-- Step 7: Set task categories based on department
UPDATE tasks t 
LEFT JOIN users u ON t.assigned_to = u.id 
LEFT JOIN departments d ON u.department_id = d.id 
SET t.task_category = (
    SELECT tc.category_name 
    FROM task_categories tc 
    WHERE BINARY tc.department_name = BINARY d.name
    LIMIT 1
)
WHERE t.task_category IS NULL AND d.name IS NOT NULL;

-- Handle department variations
UPDATE tasks t 
LEFT JOIN users u ON t.assigned_to = u.id 
LEFT JOIN departments d ON u.department_id = d.id 
SET t.task_category = 'Development'
WHERE t.task_category IS NULL AND (d.name LIKE '%IT%' OR d.name LIKE '%Information%');

UPDATE tasks t 
LEFT JOIN users u ON t.assigned_to = u.id 
LEFT JOIN departments d ON u.department_id = d.id 
SET t.task_category = 'Accounting'
WHERE t.task_category IS NULL AND (d.name LIKE '%Finance%' OR d.name LIKE '%Account%');

-- Set default category
UPDATE tasks SET task_category = 'General' WHERE task_category IS NULL;

-- Step 8: Remove unused tables with foreign key handling
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables with foreign key dependencies first
DROP TABLE IF EXISTS daily_task_updates;
DROP TABLE IF EXISTS daily_plan_items;
DROP TABLE IF EXISTS daily_reports;
DROP TABLE IF EXISTS task_updates;
DROP TABLE IF EXISTS task_comments;
DROP TABLE IF EXISTS task_dependencies;
DROP TABLE IF EXISTS task_time_logs;
DROP TABLE IF EXISTS followup_items;
DROP TABLE IF EXISTS followup_reminders;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS user_departments;
DROP TABLE IF EXISTS user_points;
DROP TABLE IF EXISTS admin_positions;
DROP TABLE IF EXISTS advances;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS activity_logs;

-- Drop main unused tables
DROP TABLE IF EXISTS daily_plans;
DROP TABLE IF EXISTS daily_planners;
DROP TABLE IF EXISTS automation_triggers;
DROP TABLE IF EXISTS automation_log;
DROP TABLE IF EXISTS badge_definitions;
DROP TABLE IF EXISTS carry_forward_rules;
DROP TABLE IF EXISTS circulars;
DROP TABLE IF EXISTS daily_tasks;
DROP TABLE IF EXISTS daily_workflow_status;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS smart_categories;
DROP TABLE IF EXISTS sync_log;
DROP TABLE IF EXISTS sync_queue;
DROP TABLE IF EXISTS user_devices;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS unified_entries;
DROP TABLE IF EXISTS geofence_locations;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS security_logs;
DROP TABLE IF EXISTS task_templates;
DROP TABLE IF EXISTS followup_categories;
DROP TABLE IF EXISTS unified_dashboard_view;

SET FOREIGN_KEY_CHECKS = 1;