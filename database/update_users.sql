-- Update login credentials for Athenas
-- Run this SQL script to update the user credentials

-- Clear existing users
DELETE FROM users;

-- Insert new users with proper credentials
INSERT INTO users (name, email, password, role, status) VALUES 
('Athenas Owner', 'info@athenas.co.in', '$2y$10$vQKQzKQzKQzKQzKQzKQzKOeKfKfKfKfKfKfKfKfKfKfKfKfKfKfKf.', 'owner', 'active'),
('Athenas Admin', 'admin@athenas.co.in', '$2y$10$aQaQaQaQaQaQaQaQaQaQaOeKfKfKfKfKfKfKfKfKfKfKfKfKfKfKf.', 'admin', 'active');

-- Note: Passwords need to be properly hashed
-- Owner password: @Athenas2025@
-- Admin password: Admin@2025@