-- Enhanced Daily Workflow Schema
-- Integrates Daily Planner with Progress Tracking

-- Drop existing tables if they exist (order matters due to foreign key constraints)
DROP TABLE IF EXISTS daily_task_updates;
DROP TABLE IF EXISTS daily_tasks;
DROP TABLE IF EXISTS daily_plans;
DROP TABLE IF EXISTS daily_task_entries;
DROP TABLE IF EXISTS project_tasks;
DROP TABLE IF EXISTS task_categories;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS daily_workflow_status;

-- Enhanced Daily Plans table with department-specific categories
CREATE TABLE daily_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department_id INT,
    plan_date DATE NOT NULL,
    project_name VARCHAR(200),
    title VARCHAR(200) NOT NULL,
    description TEXT,
    task_category VARCHAR(100),
    category ENUM('planned', 'unplanned') DEFAULT 'planned',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_hours DECIMAL(4,2) DEFAULT 1.0,
    status ENUM('pending', 'in_progress', 'completed', 'blocked', 'cancelled') DEFAULT 'pending',
    progress INT DEFAULT 0,
    actual_hours DECIMAL(4,2) DEFAULT 0,
    completion_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, plan_date),
    INDEX idx_status (status),
    INDEX idx_date (plan_date),
    INDEX idx_department (department_id)
);

-- Daily Task Updates table (for progress tracking)
CREATE TABLE daily_task_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_id INT NOT NULL,
    progress_before INT DEFAULT 0,
    progress_after INT NOT NULL,
    hours_worked DECIMAL(4,2) DEFAULT 0,
    update_notes TEXT,
    blockers TEXT,
    next_steps TEXT,
    update_type ENUM('progress', 'completion', 'blocker', 'status_change') DEFAULT 'progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES daily_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_id (plan_id),
    INDEX idx_created_at (created_at)
);

-- Task Categories by Department
CREATE TABLE task_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department (department_name)
);

-- Projects table
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    department_id INT,
    status ENUM('active', 'completed', 'on_hold', 'cancelled', 'withheld', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_department (department_id),
    INDEX idx_status (status)
);

-- Daily Workflow Status table (simplified - no submission restrictions)
CREATE TABLE daily_workflow_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    workflow_date DATE NOT NULL,
    total_planned_tasks INT DEFAULT 0,
    total_completed_tasks INT DEFAULT 0,
    total_planned_hours DECIMAL(4,2) DEFAULT 0,
    total_actual_hours DECIMAL(4,2) DEFAULT 0,
    productivity_score DECIMAL(5,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, workflow_date),
    INDEX idx_workflow_date (workflow_date)
);

-- Insert task categories by department
INSERT INTO task_categories (department_name, category_name, description) VALUES
-- IT Department
('IT', 'Development', 'Software development and coding tasks'),
('IT', 'Testing', 'Quality assurance and testing activities'),
('IT', 'Bug Fixing', 'Error resolution and debugging'),
('IT', 'Planning', 'Project planning and architecture'),
('IT', 'Hosting', 'Server management and deployment'),
('IT', 'Maintenance', 'System maintenance and updates'),
('IT', 'Documentation', 'Technical documentation and guides'),
('IT', 'Code Review', 'Peer code review and quality checks'),

-- Accounting Department
('Accounting', 'Ledger Update', 'General ledger maintenance and updates'),
('Accounting', 'Invoice Creation', 'Customer invoice generation'),
('Accounting', 'Quotation Creation', 'Price quotation preparation'),
('Accounting', 'PO Creation', 'Purchase order generation'),
('Accounting', 'PO Follow-up', 'Purchase order tracking and follow-up'),
('Accounting', 'Payment Follow-up', 'Outstanding payment collection'),
('Accounting', 'Ledger Follow-up', 'Account reconciliation and follow-up'),
('Accounting', 'GST Follow-up', 'GST compliance and filing'),
('Accounting', 'Mail Checking', 'Email correspondence and communication'),
('Accounting', 'Financial Reporting', 'Monthly and quarterly reports'),

-- Liaison Department
('Liaison', 'Document Collection', 'Gathering required documents from clients'),
('Liaison', 'Portal Upload', 'Uploading details in government portals'),
('Liaison', 'Documentation', 'Document preparation and verification'),
('Liaison', 'Follow-up', 'Client and government office follow-ups'),
('Liaison', 'Document Submission', 'Physical document submission'),
('Liaison', 'Courier Services', 'Document dispatch and delivery'),
('Liaison', 'Client Meeting', 'Client consultation and meetings'),
('Liaison', 'Government Office Visit', 'Official visits and submissions'),

-- Statutory Team
('Statutory', 'ESI Work', 'Employee State Insurance related tasks'),
('Statutory', 'EPF Work', 'Employee Provident Fund activities'),
('Statutory', 'Mail Checking', 'Official correspondence review'),
('Statutory', 'Document Preparation', 'Statutory document creation'),
('Statutory', 'Fees Payment', 'Government fees and charges payment'),
('Statutory', 'Attendance Collection', 'Employee attendance compilation'),
('Statutory', 'Compliance Filing', 'Regulatory compliance submissions'),
('Statutory', 'Audit Support', 'Audit documentation and support'),

-- Marketing Department
('Marketing', 'Campaign Planning', 'Marketing campaign strategy and planning'),
('Marketing', 'Content Creation', 'Marketing content and material creation'),
('Marketing', 'Social Media Management', 'Social media posts and engagement'),
('Marketing', 'Lead Generation', 'Prospecting and lead identification'),
('Marketing', 'Client Presentation', 'Sales presentations and proposals'),
('Marketing', 'Market Research', 'Industry and competitor analysis'),
('Marketing', 'Event Planning', 'Marketing events and webinars'),
('Marketing', 'Email Marketing', 'Email campaigns and newsletters'),

-- Virtual Office
('Virtual Office', 'Call Handling', 'Professional call answering service'),
('Virtual Office', 'Mail Management', 'Physical mail handling and forwarding'),
('Virtual Office', 'Address Services', 'Business address and registration'),
('Virtual Office', 'Meeting Coordination', 'Virtual meeting setup and management'),
('Virtual Office', 'Reception Services', 'Virtual reception and customer service'),
('Virtual Office', 'Document Scanning', 'Physical document digitization'),
('Virtual Office', 'Appointment Scheduling', 'Calendar and appointment management'),
('Virtual Office', 'Administrative Support', 'General administrative assistance');

-- Insert sample projects
INSERT INTO projects (name, description, department_id, status) VALUES
('ERGON Development', 'Employee management system development', 1, 'active'),
('Client Portal', 'Customer self-service portal', 1, 'active'),
('GST Compliance System', 'Automated GST filing system', 2, 'active'),
('Document Management', 'Digital document processing system', 3, 'active');

-- Insert sample data
INSERT INTO daily_plans (user_id, department_id, plan_date, project_name, title, task_category, description, priority, estimated_hours, status, progress) VALUES
(1, 1, CURDATE(), 'ERGON Development', 'Review Project Documentation', 'Documentation', 'Go through all project docs and update requirements', 'high', 2.0, 'in_progress', 60),
(1, 1, CURDATE(), 'Client Portal', 'Bug Fixing Session', 'Bug Fixing', 'Fix reported issues in client portal', 'urgent', 1.5, 'pending', 0),
(1, 1, CURDATE(), 'ERGON Development', 'Code Review', 'Code Review', 'Review pull requests from team members', 'medium', 1.0, 'completed', 100);

INSERT INTO daily_workflow_status (user_id, workflow_date, total_planned_tasks, total_planned_hours) VALUES
(1, CURDATE(), 3, 4.5);