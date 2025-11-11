-- Minimal Dummy Data - Only what works with current structure

-- Insert basic tasks
INSERT IGNORE INTO tasks (title, description, assigned_by, assigned_to, priority, status, created_at) VALUES
('Morning Email Review', 'Check and respond to overnight emails', 1, 1, 'medium', 'assigned', NOW()),
('Daily Standup Meeting', 'Team synchronization meeting', 1, 1, 'high', 'assigned', NOW()),
('Feature Development', 'Work on new dashboard features', 1, 1, 'high', 'in_progress', NOW()),
('Code Review Session', 'Review team members pull requests', 1, 1, 'medium', 'assigned', NOW()),
('System Health Check', 'Monitor server performance and logs', 1, 1, 'low', 'completed', NOW());

-- Insert basic evening updates
INSERT IGNORE INTO evening_updates (user_id, title, accomplishments, challenges, tomorrow_plan, created_at) VALUES
(1, 'Daily Update - Yesterday', 
'Completed system monitoring, fixed bugs, reviewed code', 
'Server performance issues, team coordination challenges', 
'Complete feature development, attend meetings, review PRs', 
DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert basic departments
INSERT IGNORE INTO departments (name, description, status, created_at) VALUES
('Information Technology', 'Software development and system administration', 'active', NOW()),
('Human Resources', 'Employee management and organizational development', 'active', NOW());

-- Insert basic users
INSERT IGNORE INTO users (name, email, password, role, employee_id, status, created_at) VALUES
('Demo User', 'demo@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'DEMO001', 'active', NOW());