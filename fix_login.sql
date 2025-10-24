-- Fix login issues by adding missing columns and ensuring user exists

-- Add missing columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_first_login TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_required TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS temp_password VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL;

-- Ensure Ilayaraja user exists with correct password
DELETE FROM users WHERE email = 'ilayaraja@athenas.co.in';
INSERT INTO users (employee_id, name, email, password, role, status, is_first_login, password_reset_required) 
VALUES ('EMP002', 'Ilayaraja', 'ilayaraja@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 0, 0);

-- Verify the user was created
SELECT id, name, email, role, status FROM users WHERE email = 'ilayaraja@athenas.co.in';