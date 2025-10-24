-- Create owner user for info@athenas.co.in
INSERT INTO users (employee_id, name, email, password, role, status, is_first_login, password_reset_required) 
VALUES ('EMP001', 'Owner', 'info@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 'active', 0, 0)
ON DUPLICATE KEY UPDATE role = 'owner';