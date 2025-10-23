-- Run this SQL in your database to create planner tables

-- First ensure departments table exists
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  head_id INT,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status)
);

-- Insert sample departments if they don't exist
INSERT IGNORE INTO departments (id, name, description) VALUES 
(1, 'Human Resources', 'Employee management and organizational development'),
(2, 'Information Technology', 'Software development and technical support'),
(3, 'Finance & Accounts', 'Financial planning and accounting operations'),
(4, 'Marketing & Sales', 'Business development and customer relations'),
(5, 'Operations', 'Daily business operations and logistics');

-- Daily planner entries
CREATE TABLE IF NOT EXISTS daily_planners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NOT NULL,
  plan_date DATE NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
  estimated_hours DECIMAL(4,2) DEFAULT 0,
  actual_hours DECIMAL(4,2) DEFAULT 0,
  completion_status ENUM('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
  completion_percentage INT DEFAULT 0,
  notes TEXT,
  reminder_time TIME,
  is_reminder_sent BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, plan_date),
  INDEX idx_priority (priority),
  INDEX idx_status (completion_status)
);

-- Department-specific form templates
CREATE TABLE IF NOT EXISTS department_form_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  form_name VARCHAR(100) NOT NULL,
  form_fields JSON NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  INDEX idx_dept_active (department_id, is_active)
);

-- Department form submissions
CREATE TABLE IF NOT EXISTS department_form_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  user_id INT NOT NULL,
  planner_id INT,
  form_data JSON NOT NULL,
  submission_date DATE NOT NULL,
  status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
  approved_by INT,
  approved_at DATETIME,
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES department_form_templates(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (planner_id) REFERENCES daily_planners(id) ON DELETE SET NULL,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_date (user_id, submission_date),
  INDEX idx_template_status (template_id, status)
);

-- Insert sample form templates
INSERT IGNORE INTO department_form_templates (department_id, form_name, form_fields) VALUES 
(1, 'HR Daily Report', '{"fields":[{"name":"interviews_conducted","type":"number","label":"Interviews Conducted","required":true},{"name":"resumes_reviewed","type":"number","label":"Resumes Reviewed","required":true}]}'),
(2, 'IT Daily Report', '{"fields":[{"name":"tickets_resolved","type":"number","label":"Support Tickets Resolved","required":true},{"name":"code_commits","type":"number","label":"Code Commits","required":false}]}'),
(3, 'Finance Daily Report', '{"fields":[{"name":"invoices_processed","type":"number","label":"Invoices Processed","required":true},{"name":"payments_made","type":"number","label":"Payments Made","required":true}]}'),
(4, 'Marketing Daily Report', '{"fields":[{"name":"leads_generated","type":"number","label":"Leads Generated","required":true},{"name":"client_meetings","type":"number","label":"Client Meetings","required":true}]}'),
(5, 'Operations Daily Report', '{"fields":[{"name":"orders_processed","type":"number","label":"Orders Processed","required":true},{"name":"inventory_checks","type":"number","label":"Inventory Checks","required":true}]}');