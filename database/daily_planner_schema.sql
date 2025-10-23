-- Daily Planner and Department Forms Schema

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
  UNIQUE KEY unique_user_date_title (user_id, plan_date, title),
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

-- Insert default department form templates
INSERT IGNORE INTO department_form_templates (department_id, form_name, form_fields) VALUES 
(1, 'HR Daily Report', JSON_OBJECT(
  'fields', JSON_ARRAY(
    JSON_OBJECT('name', 'interviews_conducted', 'type', 'number', 'label', 'Interviews Conducted', 'required', true),
    JSON_OBJECT('name', 'resumes_reviewed', 'type', 'number', 'label', 'Resumes Reviewed', 'required', true),
    JSON_OBJECT('name', 'employee_issues', 'type', 'textarea', 'label', 'Employee Issues Addressed', 'required', false),
    JSON_OBJECT('name', 'training_sessions', 'type', 'number', 'label', 'Training Sessions', 'required', false)
  )
)),
(2, 'IT Daily Report', JSON_OBJECT(
  'fields', JSON_ARRAY(
    JSON_OBJECT('name', 'tickets_resolved', 'type', 'number', 'label', 'Support Tickets Resolved', 'required', true),
    JSON_OBJECT('name', 'code_commits', 'type', 'number', 'label', 'Code Commits', 'required', false),
    JSON_OBJECT('name', 'system_maintenance', 'type', 'textarea', 'label', 'System Maintenance Tasks', 'required', false),
    JSON_OBJECT('name', 'security_incidents', 'type', 'number', 'label', 'Security Incidents', 'required', true)
  )
)),
(3, 'Finance Daily Report', JSON_OBJECT(
  'fields', JSON_ARRAY(
    JSON_OBJECT('name', 'invoices_processed', 'type', 'number', 'label', 'Invoices Processed', 'required', true),
    JSON_OBJECT('name', 'payments_made', 'type', 'number', 'label', 'Payments Made', 'required', true),
    JSON_OBJECT('name', 'reconciliation_tasks', 'type', 'textarea', 'label', 'Reconciliation Tasks', 'required', false),
    JSON_OBJECT('name', 'budget_reviews', 'type', 'number', 'label', 'Budget Reviews', 'required', false)
  )
)),
(4, 'Marketing Daily Report', JSON_OBJECT(
  'fields', JSON_ARRAY(
    JSON_OBJECT('name', 'leads_generated', 'type', 'number', 'label', 'Leads Generated', 'required', true),
    JSON_OBJECT('name', 'campaigns_launched', 'type', 'number', 'label', 'Campaigns Launched', 'required', false),
    JSON_OBJECT('name', 'client_meetings', 'type', 'number', 'label', 'Client Meetings', 'required', true),
    JSON_OBJECT('name', 'social_media_posts', 'type', 'number', 'label', 'Social Media Posts', 'required', false)
  )
)),
(5, 'Operations Daily Report', JSON_OBJECT(
  'fields', JSON_ARRAY(
    JSON_OBJECT('name', 'orders_processed', 'type', 'number', 'label', 'Orders Processed', 'required', true),
    JSON_OBJECT('name', 'inventory_checks', 'type', 'number', 'label', 'Inventory Checks', 'required', true),
    JSON_OBJECT('name', 'quality_issues', 'type', 'textarea', 'label', 'Quality Issues', 'required', false),
    JSON_OBJECT('name', 'vendor_communications', 'type', 'number', 'label', 'Vendor Communications', 'required', false)
  )
));