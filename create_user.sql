-- Create Ilayaraja user directly in database
-- Run this SQL command in your MySQL/phpMyAdmin

INSERT INTO users (employee_id, name, email, password, role, status) VALUES 
('EMP002', 'Ilayaraja', 'ilayaraja@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active')
ON DUPLICATE KEY UPDATE 
password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
role = 'user',
status = 'active';