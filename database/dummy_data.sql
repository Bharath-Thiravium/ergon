-- Dummy Data for Gamification Testing
-- Run after main schema and gamification schema

-- Clean existing test data first
DELETE FROM user_badges WHERE user_id IN (SELECT id FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005'));
DELETE FROM user_points WHERE user_id IN (SELECT id FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005'));
DELETE FROM daily_workflow_status WHERE user_id IN (SELECT id FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005'));
DELETE FROM daily_plans WHERE user_id IN (SELECT id FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005'));
DELETE FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004', 'EMP005');

-- Insert test users
INSERT INTO users (employee_id, name, email, password, role, status, department, total_points) VALUES
('EMP002', 'Alice Johnson', 'alice@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'IT', 0),
('EMP003', 'Bob Smith', 'bob@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Accounting', 0),
('EMP004', 'Carol Davis', 'carol@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 'Marketing', 0),
('EMP005', 'David Wilson', 'david@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'IT', 0);

-- Insert test departments (skip if already exist)
INSERT IGNORE INTO departments (name, description, status) VALUES
('IT', 'Information Technology', 'active'),
('Accounting', 'Finance and Accounting', 'active'),
('Marketing', 'Marketing and Sales', 'active');

-- Get user IDs for reference
SET @alice_id = (SELECT id FROM users WHERE employee_id = 'EMP002');
SET @bob_id = (SELECT id FROM users WHERE employee_id = 'EMP003');
SET @carol_id = (SELECT id FROM users WHERE employee_id = 'EMP004');

-- Insert daily plans for testing (last 7 days)
INSERT INTO daily_plans (user_id, department_id, plan_date, title, description, priority, estimated_hours, status, progress, actual_hours) VALUES
-- Alice's tasks
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Setup Development Environment', 'Configure local development setup', 'high', 2.0, 'completed', 100, 2.5),
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Code Review Session', 'Review team code submissions', 'medium', 1.5, 'completed', 100, 1.0),
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Bug Fixing', 'Fix critical production bugs', 'urgent', 3.0, 'completed', 100, 3.5),
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Database Optimization', 'Optimize slow queries', 'high', 2.5, 'completed', 100, 2.0),
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Feature Development', 'Implement new user features', 'medium', 4.0, 'completed', 100, 4.5),
(@alice_id, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Testing Phase', 'Run comprehensive tests', 'high', 2.0, 'completed', 100, 2.0),
(@alice_id, 1, CURDATE(), 'Documentation Update', 'Update technical documentation', 'medium', 1.5, 'in_progress', 60, 1.0),

-- Bob's tasks
(@bob_id, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Invoice Processing', 'Process monthly invoices', 'high', 3.0, 'completed', 100, 3.0),
(@bob_id, 2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Financial Report', 'Generate quarterly report', 'urgent', 4.0, 'completed', 100, 4.5),
(@bob_id, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Tax Calculations', 'Calculate monthly tax obligations', 'high', 2.0, 'completed', 100, 2.5),
(@bob_id, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Expense Review', 'Review team expense claims', 'medium', 1.5, 'completed', 100, 1.0),
(@bob_id, 2, CURDATE(), 'Budget Planning', 'Plan next quarter budget', 'high', 3.0, 'in_progress', 40, 1.5),

-- Carol's tasks
(@carol_id, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Campaign Design', 'Design social media campaign', 'high', 3.0, 'completed', 100, 3.5),
(@carol_id, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Content Creation', 'Create blog content', 'medium', 2.0, 'completed', 100, 2.0),
(@carol_id, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Market Research', 'Research competitor strategies', 'medium', 2.5, 'completed', 100, 2.0),
(@carol_id, 3, CURDATE(), 'Client Presentation', 'Prepare client presentation', 'urgent', 2.0, 'in_progress', 75, 1.5);

-- Insert workflow status
INSERT INTO daily_workflow_status (user_id, workflow_date, total_planned_tasks, total_completed_tasks, total_planned_hours, total_actual_hours, productivity_score) VALUES
-- Alice's status
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1, 1, 2.0, 2.5, 125.0),
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1, 1, 1.5, 1.0, 66.7),
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1, 1, 3.0, 3.5, 116.7),
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1, 1, 2.5, 2.0, 80.0),
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 1, 4.0, 4.5, 112.5),
(@alice_id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, 1, 2.0, 2.0, 100.0),
(@alice_id, CURDATE(), 1, 0, 1.5, 1.0, 66.7),

-- Bob's status
(@bob_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1, 1, 3.0, 3.0, 100.0),
(@bob_id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1, 1, 4.0, 4.5, 112.5),
(@bob_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1, 1, 2.0, 2.5, 125.0),
(@bob_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 1, 1.5, 1.0, 66.7),
(@bob_id, CURDATE(), 1, 0, 3.0, 1.5, 50.0),

-- Carol's status
(@carol_id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1, 1, 3.0, 3.5, 116.7),
(@carol_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1, 1, 2.0, 2.0, 100.0),
(@carol_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 1, 2.5, 2.0, 80.0),
(@carol_id, CURDATE(), 1, 0, 2.0, 1.5, 75.0);

-- Simulate points earned from completed tasks
INSERT INTO user_points (user_id, points, reason, reference_type, reference_id) VALUES
-- Alice's points (Total: 85 points)
(@alice_id, 10, 'Task completed', 'task', NULL),
(@alice_id, 5, 'Task completed', 'task', NULL),
(@alice_id, 15, 'Task completed', 'task', NULL),
(@alice_id, 10, 'Task completed', 'task', NULL),
(@alice_id, 5, 'Task completed', 'task', NULL),
(@alice_id, 10, 'Task completed', 'task', NULL),
(@alice_id, 30, 'Weekly completion bonus', 'bonus', NULL),

-- Bob's points (Total: 50 points)
(@bob_id, 10, 'Task completed', 'task', NULL),
(@bob_id, 15, 'Task completed', 'task', NULL),
(@bob_id, 10, 'Task completed', 'task', NULL),
(@bob_id, 5, 'Task completed', 'task', NULL),
(@bob_id, 10, 'Consistency bonus', 'bonus', NULL),

-- Carol's points (Total: 35 points)
(@carol_id, 10, 'Task completed', 'task', NULL),
(@carol_id, 5, 'Task completed', 'task', NULL),
(@carol_id, 5, 'Task completed', 'task', NULL),
(@carol_id, 15, 'Quality work bonus', 'bonus', NULL);

-- Update user total points (trigger should handle this, but manual update for safety)
UPDATE users SET total_points = 85 WHERE id = @alice_id;
UPDATE users SET total_points = 50 WHERE id = @bob_id;
UPDATE users SET total_points = 35 WHERE id = @carol_id;

-- Award badges based on achievements
INSERT INTO user_badges (user_id, badge_id) VALUES
-- Alice earned multiple badges
(@alice_id, 1), -- First Task
(@alice_id, 2), -- Task Master (10+ tasks)
(@alice_id, 4), -- Point Collector (100+ points)

-- Bob earned some badges
(@bob_id, 1), -- First Task
(@bob_id, 4), -- Point Collector (100+ points)

-- Carol earned first badge
(@carol_id, 1); -- First Task