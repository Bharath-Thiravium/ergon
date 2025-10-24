-- Database updates for System Admin workflow
-- ERGON - Employee Tracker & Task Manager

-- Add system admin flag to users table
ALTER TABLE users ADD COLUMN is_system_admin BOOLEAN DEFAULT FALSE AFTER role;

-- Add system admin flag to admin_positions table
ALTER TABLE admin_positions ADD COLUMN is_system_admin BOOLEAN DEFAULT FALSE AFTER assigned_department;

-- Update existing admin_positions table if it doesn't exist
CREATE TABLE IF NOT EXISTS admin_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_department VARCHAR(100) NULL,
    permissions JSON NULL,
    is_system_admin BOOLEAN DEFAULT FALSE,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Add indexes for better performance
CREATE INDEX idx_users_system_admin ON users(is_system_admin);
CREATE INDEX idx_admin_positions_system_admin ON admin_positions(is_system_admin);
CREATE INDEX idx_users_role_status ON users(role, status);