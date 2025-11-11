-- Simple Dummy Data Population for Unified Workflow
-- This script adds basic dummy data that works with existing table structure

-- Add required columns to existing tables (ignore errors if columns exist)
ALTER TABLE tasks ADD COLUMN assigned_for ENUM('self','other') DEFAULT 'self';
ALTER TABLE tasks ADD COLUMN followup_required BOOLEAN DEFAULT FALSE;
ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL;

ALTER TABLE daily_planner ADD COLUMN completion_status ENUM('not_started','in_progress','completed','postponed') DEFAULT 'not_started';

ALTER TABLE evening_updates ADD COLUMN planner_date DATE DEFAULT NULL;
ALTER TABLE evening_updates ADD COLUMN overall_productivity INT DEFAULT 0;

-- Insert basic tasks with current dates (only use existing columns first)
INSERT IGNORE INTO tasks (title, description, assigned_by, assigned_to, priority, planned_date, status, created_at) VALUES
('Morning Email Review', 'Check and respond to overnight emails', 1, 1, 'medium', CURDATE(), 'assigned', NOW()),
('Daily Standup Meeting', 'Team synchronization meeting', 1, 1, 'high', CURDATE(), 'assigned', NOW()),
('Feature Development', 'Work on new dashboard features', 1, 1, 'high', CURDATE(), 'in_progress', NOW()),
('Code Review Session', 'Review team members pull requests', 1, 1, 'medium', CURDATE(), 'assigned', NOW()),
('Follow-up: Client Feedback', 'Collect feedback from recent project', 1, 1, 'medium', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'assigned', NOW()),
('System Health Check', 'Monitor server performance and logs', 1, 1, 'low', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'completed', NOW());

-- Update tasks with new columns
UPDATE tasks SET assigned_for = 'self', followup_required = 1 WHERE title LIKE '%Follow-up%';
UPDATE tasks SET assigned_for = 'self', followup_required = 0 WHERE title NOT LIKE '%Follow-up%';

-- Insert more tasks with all columns
INSERT IGNORE INTO tasks (title, description, assigned_by, assigned_to, assigned_for, priority, planned_date, status, followup_required, created_at) VALUES
('Team Meeting Preparation', 'Prepare agenda and materials for weekly team meeting', 1, 1, 'self', 'medium', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'assigned', 0, NOW()),
('Follow-up: Project Status', 'Check status of ongoing development projects', 1, 1, 'self', 'high', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'assigned', 1, NOW());

-- Insert basic daily planner entries
INSERT IGNORE INTO daily_planner (user_id, date, title, description, planned_start_time, planned_duration, priority_order, status, created_at) VALUES
(1, CURDATE(), 'Morning Email Review', 'Check and respond to overnight emails', '08:30:00', 30, 1, 'planned', NOW()),
(1, CURDATE(), 'Daily Standup Meeting', 'Team synchronization meeting', '09:30:00', 30, 2, 'planned', NOW()),
(1, CURDATE(), 'Feature Development', 'Work on new dashboard features', '10:00:00', 180, 3, 'planned', NOW()),
(1, CURDATE(), 'Code Review Session', 'Review team members pull requests', '14:00:00', 90, 4, 'planned', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Sprint Planning', 'Plan next sprint with team', '09:00:00', 120, 1, 'planned', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Client Demo Preparation', 'Prepare demo for client presentation', '11:00:00', 90, 2, 'planned', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'System Monitoring', 'Check server health and performance', '08:00:00', 30, 1, 'planned', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Feature Testing', 'Test new authentication features', '11:00:00', 120, 2, 'planned', NOW());

-- Update some planner entries with completion status
UPDATE daily_planner SET 
    completion_status = 'completed'
WHERE date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

UPDATE daily_planner SET 
    completion_status = 'in_progress'
WHERE date = CURDATE() AND title = 'Feature Development';

-- Insert basic evening updates
INSERT IGNORE INTO evening_updates (user_id, title, accomplishments, challenges, tomorrow_plan, overall_productivity, planner_date, created_at) VALUES
(1, 'Daily Update - Yesterday', 
'- Completed system monitoring tasks\n- Fixed authentication bug\n- Reviewed team code submissions\n- Updated project documentation', 
'- Server had minor performance issues\n- Team member needed help with complex feature\n- Client requested last-minute changes', 
'- Complete feature development\n- Attend daily standup\n- Review pending pull requests\n- Prepare for client demo', 
7, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

(1, 'Daily Update - Two Days Ago', 
'- Implemented new user interface\n- Conducted code review session\n- Resolved 3 support tickets\n- Attended team planning meeting', 
'- Database query optimization took longer than expected\n- Had to coordinate with remote team members\n- Unexpected complexity in new requirements', 
'- Focus on system monitoring\n- Test new authentication flow\n- Schedule one-on-one meetings\n- Prepare weekly status report', 
6, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Create basic departments if they don't exist
INSERT IGNORE INTO departments (name, description, status, created_at) VALUES
('Information Technology', 'Software development and system administration', 'active', NOW()),
('Human Resources', 'Employee management and organizational development', 'active', NOW()),
('Marketing', 'Brand promotion and customer engagement', 'active', NOW()),
('Finance', 'Financial planning and budget management', 'active', NOW());

-- Create basic users if they don't exist (with safe IDs)
INSERT IGNORE INTO users (name, email, password, role, employee_id, status, created_at) VALUES
('Demo User', 'demo@ergon.com', '$2y$10$example_hash_demo', 'user', 'DEMO001', 'active', NOW()),
('Test Admin', 'admin@ergon.com', '$2y$10$example_hash_admin', 'admin', 'ADMIN001', 'active', NOW());