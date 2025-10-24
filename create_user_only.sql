-- Just create/update the user - columns already exist

-- Ensure Ilayaraja user exists with correct password
DELETE FROM users WHERE email = 'ilayaraja@athenas.co.in';
INSERT INTO users (employee_id, name, email, password, role, status, is_first_login, password_reset_required) 
VALUES ('EMP002', 'Ilayaraja', 'ilayaraja@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 0, 0);

-- Verify user was created
SELECT id, name, email, role, status FROM users WHERE email = 'ilayaraja@athenas.co.in';