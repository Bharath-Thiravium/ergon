-- Department Management Schema
CREATE TABLE IF NOT EXISTS departments (
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

-- User-Department mapping for multi-department assignment
CREATE TABLE IF NOT EXISTS user_departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NOT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_dept (user_id, department_id)
);

-- User documents table
CREATE TABLE IF NOT EXISTS user_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type ENUM('profile_photo','pan_card','aadhar_card','passport','driving_license','resume','other') NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_size INT,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_type (user_id, document_type)
);

-- Enhanced users table columns
ALTER TABLE users 
ADD COLUMN employee_id VARCHAR(20) UNIQUE,
ADD COLUMN date_of_birth DATE,
ADD COLUMN gender ENUM('male','female','other'),
ADD COLUMN address TEXT,
ADD COLUMN emergency_contact VARCHAR(20),
ADD COLUMN joining_date DATE,
ADD COLUMN salary DECIMAL(10,2),
ADD COLUMN designation VARCHAR(100);

-- Sample departments
INSERT IGNORE INTO departments (name, description) VALUES 
('Human Resources', 'Employee management and organizational development'),
('Information Technology', 'Software development and technical support'),
('Finance & Accounts', 'Financial planning and accounting operations'),
('Marketing & Sales', 'Business development and customer relations'),
('Operations', 'Daily business operations and logistics');