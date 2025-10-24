-- Create admin role user
INSERT INTO users (employee_id, name, email, password, role, status, is_first_login, password_reset_required) 
VALUES ('EMP003', 'Admin User', 'admin@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 0, 0);