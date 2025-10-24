-- Fix all database errors for ERGON
-- Create missing tables and fix data issues

-- Create settings table if not exists
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'ERGON Company',
    attendance_radius INT DEFAULT 200,
    backup_email VARCHAR(255) NULL,
    base_location_lat DECIMAL(10,8) DEFAULT 0,
    base_location_lng DECIMAL(11,8) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings if none exist
INSERT IGNORE INTO settings (id, company_name, attendance_radius, backup_email, base_location_lat, base_location_lng)
VALUES (1, 'ERGON Company', 200, '', 0, 0);

-- Create departments table if not exists
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    head_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default departments if none exist
INSERT IGNORE INTO departments (id, name, description, status) VALUES
(1, 'General', 'General Department', 'active'),
(2, 'IT', 'Information Technology', 'active'),
(3, 'HR', 'Human Resources', 'active'),
(4, 'Finance', 'Finance Department', 'active'),
(5, 'Operations', 'Operations Department', 'active');

-- Create admin_positions table if not exists (already exists from the active file)
CREATE TABLE IF NOT EXISTS admin_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_department VARCHAR(100) NULL,
    permissions TEXT NULL,
    is_system_admin BOOLEAN DEFAULT FALSE,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_positions_system_admin (is_system_admin),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Create daily_planner table if not exists
CREATE TABLE IF NOT EXISTS daily_planner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NULL,
    plan_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_hours DECIMAL(4,2) DEFAULT 0,
    actual_hours DECIMAL(4,2) NULL,
    completion_percentage INT DEFAULT 0,
    completion_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    reminder_time TIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_daily_planner_user_date (user_id, plan_date),
    INDEX idx_daily_planner_department (department_id)
);

-- Create sample users if none exist (for testing)
INSERT IGNORE INTO users (id, name, email, password, role, status, department, employee_id, created_at) VALUES
(1, 'System Owner', 'owner@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 'active', 'Management', 'EMP001', NOW()),
(2, 'Admin User', 'admin@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'IT', 'EMP002', NOW()),
(3, 'John Doe', 'john@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'General', 'EMP003', NOW()),
(4, 'Jane Smith', 'jane@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'HR', 'EMP004', NOW()),
(5, 'Mike Johnson', 'mike@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Finance', 'EMP005', NOW());

-- Create sample leaves for testing
INSERT IGNORE INTO leaves (id, user_id, leave_type, start_date, end_date, days_requested, reason, status, created_at) VALUES
(1, 3, 'Annual', '2024-02-15', '2024-02-17', 3, 'Family vacation', 'Pending', NOW()),
(2, 4, 'Sick', '2024-02-10', '2024-02-10', 1, 'Medical appointment', 'Approved', NOW()),
(3, 5, 'Personal', '2024-02-20', '2024-02-21', 2, 'Personal matters', 'Pending', NOW());

-- Create sample expenses for testing
INSERT IGNORE INTO expenses (id, user_id, category, amount, description, receipt_path, status, created_at) VALUES
(1, 3, 'Travel', 250.00, 'Client meeting travel expenses', NULL, 'pending', NOW()),
(2, 4, 'Food', 45.50, 'Team lunch meeting', NULL, 'approved', NOW()),
(3, 5, 'Material', 120.00, 'Office supplies', NULL, 'pending', NOW());

-- Create sample attendance records
INSERT IGNORE INTO attendance (id, user_id, clock_in_time, clock_out_time, date, location_lat, location_lng, status, created_at) VALUES
(1, 3, '09:00:00', '17:30:00', CURDATE(), 0, 0, 'present', NOW()),
(2, 4, '08:45:00', '17:15:00', CURDATE(), 0, 0, 'present', NOW()),
(3, 5, '09:15:00', NULL, CURDATE(), 0, 0, 'present', NOW());

-- Update users table to ensure department field exists and has values
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT 'General';
UPDATE users SET department = 'General' WHERE department IS NULL OR department = '';

-- Ensure all required tables exist with proper structure
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    assigned_to INT NULL,
    assigned_by INT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);