-- Basic Dummy Data - No table alterations, just data insertion
-- Works with existing table structure

-- Insert basic tasks (using only existing columns)
INSERT IGNORE INTO tasks (title, description, assigned_by, assigned_to, priority, status, created_at) VALUES
('Morning Email Review', 'Check and respond to overnight emails', 1, 1, 'medium', 'assigned', NOW()),
('Daily Standup Meeting', 'Team synchronization meeting', 1, 1, 'high', 'assigned', NOW()),
('Feature Development', 'Work on new dashboard features', 1, 1, 'high', 'in_progress', NOW()),
('Code Review Session', 'Review team members pull requests', 1, 1, 'medium', 'assigned', NOW()),
('Client Feedback Collection', 'Collect feedback from recent project delivery', 1, 1, 'medium', 'assigned', NOW()),
('System Health Check', 'Monitor server performance and logs', 1, 1, 'low', 'completed', NOW()),
('Database Backup Verification', 'Verify daily backup completion and integrity', 1, 1, 'high', 'completed', NOW()),
('Team Performance Review', 'Conduct quarterly performance reviews', 1, 1, 'medium', 'assigned', NOW()),
('Marketing Campaign Planning', 'Plan Q2 marketing initiatives', 1, 1, 'medium', 'assigned', NOW()),
('Budget Analysis Report', 'Prepare monthly budget analysis', 1, 1, 'medium', 'assigned', NOW());

-- Skip daily planner entries for now (table structure issue)
-- Will be populated by the controller's dummy data methods

-- Insert basic evening updates
INSERT IGNORE INTO evening_updates (user_id, title, accomplishments, challenges, tomorrow_plan, created_at) VALUES
(1, 'Daily Update - Yesterday', 
'- Completed system monitoring tasks
- Fixed authentication bug in user login
- Reviewed and approved 2 pull requests
- Updated project documentation
- Resolved 3 customer support tickets', 
'- Server had minor performance issues during peak hours
- Team member needed help with complex feature implementation
- Client requested last-minute changes to requirements
- Database query optimization took longer than expected', 
'- Complete feature development work
- Attend daily standup meeting
- Review pending pull requests from team
- Prepare demo for client presentation
- Schedule team planning session', 
DATE_SUB(NOW(), INTERVAL 1 DAY)),

(1, 'Daily Update - Two Days Ago', 
'- Implemented new user interface components
- Conducted code review session with junior developers
- Resolved 4 support tickets
- Attended team planning meeting
- Updated API documentation', 
'- Browser compatibility issues with older versions
- Had to coordinate with remote team members across time zones
- Unexpected complexity in new feature requirements
- Limited time for thorough testing due to tight deadline', 
'- Focus on system monitoring and health checks
- Test new authentication flow thoroughly
- Schedule one-on-one meetings with team members
- Prepare weekly status report for management
- Review and update security protocols', 
DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert basic departments
INSERT IGNORE INTO departments (name, description, status, created_at) VALUES
('Information Technology', 'Software development and system administration', 'active', NOW()),
('Human Resources', 'Employee management and organizational development', 'active', NOW()),
('Marketing', 'Brand promotion and customer engagement', 'active', NOW()),
('Finance', 'Financial planning and budget management', 'active', NOW()),
('Operations', 'Daily business operations and process management', 'active', NOW());

-- Insert basic users (only if they don't exist)
INSERT IGNORE INTO users (name, email, password, role, employee_id, status, created_at) VALUES
('Demo User', 'demo@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'DEMO001', 'active', NOW()),
('Test Admin', 'testadmin@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ADMIN001', 'active', NOW()),
('Sample Manager', 'manager@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'MGR001', 'active', NOW());