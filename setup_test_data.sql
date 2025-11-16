-- Clear existing task data
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM daily_tasks;
DELETE FROM tasks;
DELETE FROM followups;
SET FOREIGN_KEY_CHECKS = 1;

-- Reset auto increment
ALTER TABLE tasks AUTO_INCREMENT = 1;

-- Get current user ID (replace 1 with your actual user ID)
SELECT 'Using user ID 1 - change this if your user ID is different' as notice;

-- Insert 20 test tasks
-- Insert tasks for user ID 1 (change all instances of 1 to your user ID if different)
INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, status, progress, sla_hours, task_category, planned_date, created_at) VALUES
('Client Meeting Preparation', 'Prepare presentation materials for ABC Corp meeting', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 3 DAY), 'assigned', 0, 2, 'meeting', CURDATE(), NOW()),
('Database Optimization', 'Optimize database queries for better performance', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 5 DAY), 'in_progress', 25, 4, 'development', CURDATE(), NOW()),
('Email Campaign Setup', 'Create and schedule monthly newsletter campaign', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 7 DAY), 'assigned', 0, 3, 'marketing', DATE_ADD(CURDATE(), INTERVAL 1 DAY), NOW()),
('Security Audit Review', 'Review security audit findings and create action plan', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 2 DAY), 'assigned', 0, 6, 'security', CURDATE(), NOW()),
('User Interface Updates', 'Update dashboard UI based on user feedback', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 8 DAY), 'in_progress', 40, 5, 'development', CURDATE(), NOW()),
('Budget Report Analysis', 'Analyze Q4 budget reports and prepare summary', 1, 1, 'ad-hoc', 'low', DATE_ADD(NOW(), INTERVAL 10 DAY), 'assigned', 0, 2, 'finance', DATE_ADD(CURDATE(), INTERVAL 2 DAY), NOW()),
('Team Training Session', 'Conduct training on new project management tools', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 6 DAY), 'assigned', 0, 4, 'training', CURDATE(), NOW()),
('Server Maintenance', 'Perform routine server maintenance and updates', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 1 DAY), 'assigned', 0, 3, 'maintenance', CURDATE(), NOW()),
('Customer Feedback Review', 'Review and categorize customer feedback from last month', 1, 1, 'ad-hoc', 'low', DATE_ADD(NOW(), INTERVAL 12 DAY), 'assigned', 0, 2, 'support', DATE_ADD(CURDATE(), INTERVAL 3 DAY), NOW()),
('Product Documentation', 'Update product documentation for new features', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 9 DAY), 'assigned', 0, 4, 'documentation', DATE_ADD(CURDATE(), INTERVAL 1 DAY), NOW()),
('Sales Report Generation', 'Generate monthly sales reports for management', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 4 DAY), 'assigned', 0, 3, 'reporting', DATE_ADD(CURDATE(), INTERVAL 2 DAY), NOW()),
('Website Content Update', 'Update website content and fix broken links', 1, 1, 'ad-hoc', 'low', DATE_ADD(NOW(), INTERVAL 11 DAY), 'assigned', 0, 2, 'content', DATE_ADD(CURDATE(), INTERVAL 4 DAY), NOW()),
('API Integration Testing', 'Test new API integrations with third-party services', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 3 DAY), 'assigned', 0, 5, 'testing', DATE_ADD(CURDATE(), INTERVAL 1 DAY), NOW()),
('Employee Onboarding', 'Prepare onboarding materials for new employees', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 7 DAY), 'assigned', 0, 3, 'hr', DATE_ADD(CURDATE(), INTERVAL 3 DAY), NOW()),
('Backup System Check', 'Verify backup systems are working correctly', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 2 DAY), 'assigned', 0, 2, 'maintenance', CURDATE(), NOW()),
('Social Media Strategy', 'Develop social media strategy for next quarter', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 14 DAY), 'assigned', 0, 4, 'marketing', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NOW()),
('Code Review Process', 'Review and approve pending code changes', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 5 DAY), 'in_progress', 60, 3, 'development', CURDATE(), NOW()),
('Vendor Contract Review', 'Review contracts with software vendors', 1, 1, 'ad-hoc', 'low', DATE_ADD(NOW(), INTERVAL 13 DAY), 'assigned', 0, 2, 'legal', DATE_ADD(CURDATE(), INTERVAL 6 DAY), NOW()),
('Performance Metrics', 'Analyze team performance metrics and KPIs', 1, 1, 'ad-hoc', 'medium', DATE_ADD(NOW(), INTERVAL 8 DAY), 'assigned', 0, 3, 'analytics', DATE_ADD(CURDATE(), INTERVAL 2 DAY), NOW()),
('Project Planning', 'Plan next sprint activities and resource allocation', 1, 1, 'ad-hoc', 'high', DATE_ADD(NOW(), INTERVAL 4 DAY), 'assigned', 0, 4, 'planning', DATE_ADD(CURDATE(), INTERVAL 1 DAY), NOW());

-- Create daily tasks for today (linked to tasks table)
INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at) VALUES
(1, 1, CURDATE(), 'Client Meeting Preparation', 'Prepare presentation materials for ABC Corp meeting', 120, 'high', 'not_started', NOW()),
(1, 2, CURDATE(), 'Database Optimization', 'Optimize database queries for better performance', 240, 'medium', 'not_started', NOW()),
(1, 3, CURDATE(), 'Email Campaign Setup', 'Create and schedule monthly newsletter campaign', 180, 'medium', 'not_started', NOW()),
(1, 4, CURDATE(), 'Security Audit Review', 'Review security audit findings and create action plan', 360, 'high', 'not_started', NOW()),
(1, 5, CURDATE(), 'User Interface Updates', 'Update dashboard UI based on user feedback', 300, 'medium', 'not_started', NOW()),
(1, 6, CURDATE(), 'Budget Report Analysis', 'Analyze Q4 budget reports and prepare summary', 120, 'low', 'not_started', NOW()),
(1, 7, CURDATE(), 'Team Training Session', 'Conduct training on new project management tools', 240, 'medium', 'not_started', NOW()),
(1, 8, CURDATE(), 'Server Maintenance', 'Perform routine server maintenance and updates', 180, 'high', 'not_started', NOW());

-- Create follow-ups linked to tasks
INSERT INTO followups (user_id, task_id, title, description, company_name, follow_up_date, original_date, reminder_time, status, created_at) VALUES
(1, 11, 'Follow-up: Sales Report Generation', 'Follow-up required for monthly sales reports', 'ABC Corporation', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'pending', NOW()),
(1, 12, 'Follow-up: Website Content Update', 'Follow-up required for website updates', 'XYZ Ltd', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:30:00', 'pending', NOW()),
(1, 13, 'Follow-up: API Integration Testing', 'Follow-up required for API testing completion', 'Tech Solutions Inc', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'pending', NOW()),
(1, 14, 'Follow-up: Employee Onboarding', 'Follow-up on onboarding process completion', 'HR Department', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), '11:00:00', 'pending', NOW()),
(1, 15, 'Follow-up: Backup System Check', 'Follow-up on backup system verification', 'IT Operations', CURDATE(), CURDATE(), '16:00:00', 'pending', NOW()),
(1, 16, 'Follow-up: Social Media Strategy', 'Follow-up on strategy development progress', 'Marketing Team', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), '13:30:00', 'pending', NOW());

-- Add more daily tasks for different dates to test calendar
INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at) VALUES
(1, 9, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Customer Feedback Review', 'Review and categorize customer feedback from last month', 120, 'low', 'not_started', NOW()),
(1, 10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Product Documentation', 'Update product documentation for new features', 240, 'medium', 'not_started', NOW()),
(1, 19, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Performance Metrics', 'Analyze team performance metrics and KPIs', 180, 'medium', 'not_started', NOW()),
(1, 20, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Project Planning', 'Plan next sprint activities and resource allocation', 240, 'high', 'not_started', NOW());

-- Update some tasks to show they require follow-up
UPDATE tasks SET task_category = 'follow-up' WHERE id IN (11, 12, 13, 14, 15, 16);

-- Verify data was inserted
SELECT 'Tasks created:' as info, COUNT(*) as count FROM tasks;
SELECT 'Daily tasks created:' as info, COUNT(*) as count FROM daily_tasks;
SELECT 'Follow-ups created:' as info, COUNT(*) as count FROM followups;

-- Show sample data
SELECT 'Sample tasks for today:' as info;
SELECT id, title, assigned_to, planned_date FROM tasks WHERE planned_date = CURDATE() LIMIT 5;

SELECT 'Sample daily tasks:' as info;
SELECT id, title, user_id, scheduled_date FROM daily_tasks WHERE scheduled_date = CURDATE() LIMIT 5;

SELECT 'Sample follow-ups:' as info;
SELECT id, title, user_id, follow_up_date FROM followups LIMIT 5;