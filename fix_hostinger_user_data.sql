-- Fix Hostinger user data - ensure user exists with all required fields

-- First, check if user exists
SELECT * FROM users WHERE id = 2;

-- If user doesn't exist, create it
INSERT IGNORE INTO users (id, employee_id, name, email, password, role, status, is_first_login, password_reset_required, phone, department, created_at) 
VALUES (2, 'EMP002', 'Athenas Admin', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 0, 0, '', '', NOW());

-- Check if EMP002 is already used by another user
SELECT id, employee_id FROM users WHERE employee_id = 'EMP002';

-- Update existing user (skip employee_id if already exists)
UPDATE users SET 
    name = 'Athenas Admin', 
    email = 'admin@athenas.co.in',
    role = 'admin',
    status = 'active',
    is_first_login = 0,
    password_reset_required = 0
WHERE id = 2;

-- Skip employee_id update since EMP002 already exists for another user
-- User ID 2 will keep its current employee_id

-- Verify the user data
SELECT id, employee_id, name, email, role, status, is_first_login, password_reset_required FROM users WHERE id = 2;

-- Test query that admin dashboard uses
SELECT COUNT(*) as total_users FROM users WHERE status = 'active';

-- Create basic tables if they don't exist (for stats to work)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    assigned_to INT,
    deadline DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);