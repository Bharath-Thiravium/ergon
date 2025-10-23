-- 🧩 ERGON Database Schema
-- Employee Tracker & Task Manager for MSMEs (PHP + MySQL)

-- Drop existing tables if they exist (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS department_form_submissions;
DROP TABLE IF EXISTS user_departments;
DROP TABLE IF EXISTS user_documents;
DROP TABLE IF EXISTS daily_planners;
DROP TABLE IF EXISTS department_form_templates;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS task_updates;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS advances;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS leaves;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS circulars;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

-- 🔖 1. users - Stores user credentials, profiles, and role-based access
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id VARCHAR(20) UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('owner','admin','user') DEFAULT 'user',
  phone VARCHAR(20),
  department VARCHAR(100),
  status ENUM('active','inactive') DEFAULT 'active',
  is_first_login BOOLEAN DEFAULT TRUE,
  temp_password VARCHAR(20),
  password_reset_required BOOLEAN DEFAULT FALSE,
  last_login DATETIME,
  last_ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_status (status),
  INDEX idx_employee_id (employee_id)
);

-- 📍 2. attendance - Tracks GPS-based check-ins and check-outs
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  check_in DATETIME,
  check_out DATETIME,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  location_name VARCHAR(255),
  distance_from_site DECIMAL(10,2),
  status ENUM('present','absent','manual','pending') DEFAULT 'pending',
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, created_at),
  INDEX idx_status (status)
);

-- 🗓️ 3. leaves - Stores leave requests with workflow tracking
CREATE TABLE leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  approved_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_employee_status (employee_id, status),
  INDEX idx_type (type)
);

-- 💰 4. expenses - Stores uploaded receipts, categories, and approval data
CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category VARCHAR(100),
  amount DECIMAL(10,2),
  description TEXT,
  receipt_path VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  approved_by INT,
  approved_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_status (user_id, status),
  INDEX idx_category (category)
);

-- 💸 5. advances - Stores advance requests with repayment tracking
CREATE TABLE advances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  reason TEXT NOT NULL,
  repayment_date DATE,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  approved_by INT,
  approved_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_status (user_id, status),
  INDEX idx_type (type)
);

-- ⚙️ 6. tasks - Stores task definitions assigned by admins or owners
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  description TEXT,
  assigned_by INT NOT NULL,
  assigned_to INT NOT NULL,
  task_type ENUM('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
  priority ENUM('low','medium','high') DEFAULT 'medium',
  deadline DATETIME,
  progress INT DEFAULT 0,
  status ENUM('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_assigned_to (assigned_to),
  INDEX idx_status (status),
  INDEX idx_priority (priority)
);

-- 🗂️ 7. task_updates - Versioned updates for progress tracking and attachments
CREATE TABLE task_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  progress INT,
  comment TEXT,
  attachment VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_task_id (task_id),
  INDEX idx_created_at (created_at)
);

-- 🧾 8. audit_logs - Complete action tracking for traceability and rollback
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  module VARCHAR(100),
  action VARCHAR(100),
  description TEXT,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_module (user_id, module),
  INDEX idx_created_at (created_at)
);

-- 🧱 9. settings - Centralized system configuration
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(150),
  logo_path VARCHAR(255),
  base_location_lat DECIMAL(10,8),
  base_location_lng DECIMAL(11,8),
  attendance_radius INT DEFAULT 200,
  backup_email VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 🔄 10. approvals - Unified approval workflow
CREATE TABLE approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(50) NOT NULL,
  record_id INT NOT NULL,
  requested_by INT NOT NULL,
  approved_by INT,
  status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 📢 11. circulars - Internal communication
CREATE TABLE circulars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  posted_by INT NOT NULL,
  visible_to ENUM('All','Admin','User') DEFAULT 'All',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 🏢 12. departments - Department management
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  head_id INT,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status (status)
);

-- 👥 13. user_departments - User-Department mapping
CREATE TABLE user_departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NOT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_dept (user_id, department_id)
);

-- 14. daily_planners - Daily task planning
CREATE TABLE daily_planners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT,
  plan_date DATE NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
  estimated_hours DECIMAL(4,2),
  actual_hours DECIMAL(4,2),
  completion_status ENUM('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
  completion_percentage INT DEFAULT 0,
  notes TEXT,
  reminder_time TIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_user_date (user_id, plan_date),
  INDEX idx_status (completion_status)
);

-- 15. activity_logs - Smart activity tracking for IT department
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  activity_type ENUM('login','logout','task_update','break_start','break_end','system_ping') DEFAULT 'system_ping',
  description TEXT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_activity (user_id, created_at),
  INDEX idx_activity_type (activity_type)
);

-- Insert default settings
INSERT INTO settings (company_name, attendance_radius) VALUES ('ERGON Company', 200);

-- Insert default departments
INSERT IGNORE INTO departments (name, description) VALUES 
('Human Resources', 'Employee management and organizational development'),
('Information Technology', 'Software development and technical support'),
('Finance & Accounts', 'Financial planning and accounting operations'),
('Marketing & Sales', 'Business development and customer relations'),
('Operations', 'Daily business operations and logistics');

-- Insert default users - only if not exists
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('Athenas Owner', 'info@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner'),
('Athenas Admin', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');