-- Fix Hostinger edit issue - create missing user ID 2 or use existing users

-- Option 1: Create user ID 2 (admin)
INSERT INTO users (id, employee_id, name, email, password, role, status, is_first_login, password_reset_required, phone, department, created_at) 
VALUES (2, 'EMP003', 'Athenas Admin', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 0, 0, '', '', NOW());

-- Option 2: Update existing user to admin role (use ID 6)
UPDATE users SET role = 'admin' WHERE id = 6;