-- ERGON Database Reset Script
-- WARNING: This will delete ALL data including owner account

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Delete all data from existing tables only
DELETE FROM users WHERE 1=1;
DELETE FROM departments WHERE 1=1;
DELETE FROM tasks WHERE 1=1;
DELETE FROM attendance WHERE 1=1;
DELETE FROM leaves WHERE 1=1;
DELETE FROM expenses WHERE 1=1;
DELETE FROM advances WHERE 1=1;
DELETE FROM notifications WHERE 1=1;

-- Reset auto increment for existing tables
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE departments AUTO_INCREMENT = 1;
ALTER TABLE tasks AUTO_INCREMENT = 1;
ALTER TABLE attendance AUTO_INCREMENT = 1;
ALTER TABLE leaves AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;
ALTER TABLE advances AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;

SELECT 'Database reset completed successfully!' as Status;