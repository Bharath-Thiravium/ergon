-- Clean Dummy Data Script
-- Run this first if you need to reset test data

-- Remove test data
DELETE FROM user_badges WHERE user_id IN (2,3,4,5);
DELETE FROM user_points WHERE user_id IN (2,3,4,5);
DELETE FROM daily_workflow_status WHERE user_id IN (2,3,4,5);
DELETE FROM daily_plans WHERE user_id IN (2,3,4,5);
DELETE FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005');

-- Reset auto increment if needed
-- ALTER TABLE users AUTO_INCREMENT = 2;

SELECT 'Test data cleaned successfully' as status;