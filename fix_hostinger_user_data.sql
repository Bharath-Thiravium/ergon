-- Fix Hostinger user data - ensure user exists with all required fields

-- First, check if user exists
SELECT * FROM users WHERE id = 2;

-- If user doesn't exist, create it
INSERT IGNORE INTO users (id, employee_id, name, email, password, role, status, is_first_login, password_reset_required, phone, department, created_at) 
VALUES (2, 'EMP002', 'Athenas Admin', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 0, 0, '', '', NOW());

-- Update existing user if needed
UPDATE users SET 
    employee_id = 'EMP002',
    name = 'Athenas Admin', 
    email = 'admin@athenas.co.in',
    role = 'admin',
    status = 'active',
    is_first_login = 0,
    password_reset_required = 0
WHERE id = 2;