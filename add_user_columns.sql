-- Simple user data fix for both Laragon and Hostinger

-- Check current table structure
DESCRIBE users;

-- Ensure user ID 2 exists with basic data
INSERT IGNORE INTO users (id, name, email, password, role, status, phone, department, created_at) 
VALUES (2, 'Admin User', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '1234567890', 'Administration', NOW());

-- Update existing user with required data (skip employee_id)
UPDATE users SET 
    name = 'Admin User',
    email = 'admin@athenas.co.in',
    role = 'admin',
    status = 'active',
    phone = COALESCE(phone, '1234567890'),
    department = COALESCE(department, 'Administration'),
    designation = COALESCE(designation, 'System Administrator'),
    joining_date = COALESCE(joining_date, CURDATE())
WHERE id = 2;

-- Check the user data
SELECT id, name, email, role, status, phone, department FROM users WHERE id = 2;