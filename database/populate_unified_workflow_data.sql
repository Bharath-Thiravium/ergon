-- Populate Unified Workflow System with Dummy Data
-- Run this after the unified_workflow_migration.sql

-- Insert sample tasks with unified workflow fields
INSERT IGNORE INTO tasks (id, title, description, assigned_by, assigned_to, assigned_for, task_type, priority, deadline, planned_date, status, progress, followup_required, sla_hours, department_id, task_category, created_at) VALUES
(101, 'Client Meeting Preparation', 'Prepare presentation materials for ABC Corp meeting', 1, 1, 'self', 'ad-hoc', 'high', '2024-02-20 14:00:00', '2024-02-19', 'in_progress', 75, 1, 24, 1, 'Meeting Prep', NOW()),
(102, 'Follow-up: XYZ Project Status', 'Check project progress with development team', 1, 2, 'other', 'followup', 'medium', '2024-02-21 10:00:00', '2024-02-21', 'assigned', 0, 1, 48, 1, 'Follow-up', NOW()),
(103, 'Database Backup Verification', 'Verify daily backup completion and integrity', 1, 1, 'self', 'checklist', 'high', '2024-02-19 09:00:00', '2024-02-19', 'completed', 100, 0, 24, 1, 'Maintenance', NOW()),
(104, 'Team Performance Review', 'Conduct quarterly performance reviews', 1, 3, 'other', 'milestone', 'medium', '2024-02-25 15:00:00', '2024-02-22', 'assigned', 0, 1, 168, 2, 'HR Review', NOW()),
(105, 'Follow-up: Client Feedback Collection', 'Collect feedback from recent project delivery', 1, 1, 'self', 'followup', 'medium', '2024-02-20 11:00:00', '2024-02-20', 'assigned', 0, 1, 72, 2, 'Follow-up', NOW()),
(106, 'Code Review: Authentication Module', 'Review security implementation in auth system', 1, 2, 'other', 'timed', 'high', '2024-02-19 16:00:00', '2024-02-19', 'in_progress', 60, 0, 8, 1, 'Code Review', NOW()),
(107, 'Marketing Campaign Planning', 'Plan Q2 marketing initiatives', 1, 4, 'other', 'milestone', 'medium', '2024-02-28 17:00:00', '2024-02-23', 'assigned', 0, 0, 120, 3, 'Campaign', NOW()),
(108, 'System Health Check', 'Monitor server performance and logs', 1, 1, 'self', 'checklist', 'low', '2024-02-19 08:00:00', '2024-02-19', 'completed', 100, 0, 24, 1, 'Monitoring', NOW()),
(109, 'Follow-up: Training Session Feedback', 'Gather feedback from last weeks training', 1, 3, 'other', 'followup', 'low', '2024-02-21 14:00:00', '2024-02-21', 'assigned', 0, 1, 48, 2, 'Follow-up', NOW()),
(110, 'Budget Analysis Report', 'Prepare monthly budget analysis', 1, 5, 'other', 'ad-hoc', 'medium', '2024-02-22 12:00:00', '2024-02-22', 'assigned', 0, 0, 72, 4, 'Finance', NOW());

-- Insert daily planner entries
INSERT IGNORE INTO daily_planner (id, user_id, date, task_id, task_type, title, description, planned_start_time, planned_duration, priority_order, status, completion_status, actual_start_time, actual_end_time, notes, created_at) VALUES
(201, 1, '2024-02-19', 101, 'personal', 'Client Meeting Preparation', 'Prepare presentation materials for ABC Corp meeting', '09:00:00', 120, 1, 'planned', 'in_progress', '09:15:00', NULL, 'Started late due to email backlog', NOW()),
(202, 1, '2024-02-19', 103, 'personal', 'Database Backup Verification', 'Verify daily backup completion and integrity', '08:00:00', 60, 2, 'planned', 'completed', '08:00:00', '08:45:00', 'All backups verified successfully', NOW()),
(203, 1, '2024-02-19', 108, 'personal', 'System Health Check', 'Monitor server performance and logs', '08:00:00', 30, 3, 'planned', 'completed', '08:45:00', '09:00:00', 'All systems running normally', NOW()),
(204, 1, '2024-02-19', 106, 'personal', 'Code Review: Authentication Module', 'Review security implementation in auth system', '14:00:00', 180, 4, 'planned', 'in_progress', '14:30:00', NULL, 'Found minor security improvements needed', NOW()),
(205, 1, '2024-02-20', 105, 'personal', 'Follow-up: Client Feedback Collection', 'Collect feedback from recent project delivery', '11:00:00', 90, 1, 'planned', 'not_started', NULL, NULL, NULL, NOW()),
(206, 2, '2024-02-21', 102, 'personal', 'Follow-up: XYZ Project Status', 'Check project progress with development team', '10:00:00', 60, 1, 'planned', 'not_started', NULL, NULL, NULL, NOW()),
(207, 3, '2024-02-22', 104, 'personal', 'Team Performance Review', 'Conduct quarterly performance reviews', '15:00:00', 240, 1, 'planned', 'not_started', NULL, NULL, NULL, NOW()),
(208, 1, '2024-02-21', NULL, 'personal', 'Email Management', 'Process and respond to pending emails', '09:00:00', 60, 1, 'planned', 'not_started', NULL, NULL, NULL, NOW()),
(209, 1, '2024-02-21', NULL, 'personal', 'Weekly Team Standup', 'Attend weekly development team meeting', '10:30:00', 30, 2, 'planned', 'not_started', NULL, NULL, NULL, NOW()),
(210, 2, '2024-02-20', NULL, 'personal', 'Documentation Update', 'Update API documentation for new features', '14:00:00', 120, 1, 'planned', 'not_started', NULL, NULL, NULL, NOW());

-- Update daily planner entries with completion status after insert
UPDATE daily_planner SET 
    completion_status = CASE id
        WHEN 201 THEN 'in_progress'
        WHEN 202 THEN 'completed'
        WHEN 203 THEN 'completed'
        WHEN 209 THEN 'completed'
        WHEN 210 THEN 'completed'
        ELSE 'not_started'
    END,
    actual_start_time = CASE id
        WHEN 201 THEN '09:15:00'
        WHEN 202 THEN '08:00:00'
        WHEN 203 THEN '08:45:00'
        WHEN 209 THEN '08:00:00'
        WHEN 210 THEN '11:15:00'
        ELSE NULL
    END,
    actual_end_time = CASE id
        WHEN 202 THEN '08:45:00'
        WHEN 203 THEN '09:00:00'
        WHEN 209 THEN '08:25:00'
        WHEN 210 THEN '13:00:00'
        ELSE NULL
    END,
    notes = CASE id
        WHEN 201 THEN 'Started late due to email backlog'
        WHEN 202 THEN 'All backups verified successfully'
        WHEN 203 THEN 'All systems running normally'
        WHEN 209 THEN 'All systems running smoothly'
        WHEN 210 THEN 'Found minor UI issues, created tickets'
        ELSE NULL
    END
WHERE id IN (201, 202, 203, 209, 210);

-- Ensure evening_updates table has the required columns
ALTER TABLE evening_updates ADD COLUMN IF NOT EXISTS planner_date DATE DEFAULT NULL;
ALTER TABLE evening_updates ADD COLUMN IF NOT EXISTS overall_productivity INT DEFAULT 0;

-- Insert evening updates
INSERT IGNORE INTO evening_updates (id, user_id, title, accomplishments, challenges, tomorrow_plan, created_at) VALUES
(301, 1, 'Daily Update - Today', 
'- Completed database optimization tasks\n- Reviewed and approved 3 pull requests\n- Fixed critical bug in payment processing\n- Attended client call for project requirements', 
'- Deployment pipeline had issues causing delays\n- Team member was sick, had to cover additional tasks\n- Client changed requirements mid-sprint', 
'- Complete client presentation preparation\n- Finish code review for authentication module\n- Start working on new feature implementation\n- Schedule team meeting for sprint planning', 
DATE_SUB(NOW(), INTERVAL 1 DAY)),

(302, 1, 'Daily Update - Yesterday', 
'- Implemented new user authentication flow\n- Conducted code review session with junior developers\n- Updated project documentation\n- Resolved 5 support tickets', 
'- Server performance issues during peak hours\n- Difficulty coordinating with remote team members\n- Unexpected complexity in new feature requirements', 
'- Investigate server performance bottlenecks\n- Schedule one-on-one meetings with team\n- Break down complex feature into smaller tasks\n- Prepare for client demo on Friday', 
DATE_SUB(NOW(), INTERVAL 2 DAY)),

(303, 1, 'Daily Update - Current', 
'- Completed UI mockups for new dashboard\n- Tested mobile responsiveness across devices\n- Fixed styling issues in checkout flow\n- Collaborated with UX team on user journey', 
'- Browser compatibility issues with older versions\n- Design feedback required multiple iterations\n- Limited time for thorough testing', 
'- Implement responsive design improvements\n- Conduct user testing session\n- Finalize color scheme and typography\n- Prepare design handoff documentation', 
DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Update evening_updates with productivity and planner_date
UPDATE evening_updates SET 
    overall_productivity = CASE id
        WHEN 301 THEN 8
        WHEN 302 THEN 7
        WHEN 303 THEN 6
        ELSE 5
    END,
    planner_date = CASE id
        WHEN 301 THEN DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        WHEN 302 THEN DATE_SUB(CURDATE(), INTERVAL 2 DAY)
        WHEN 303 THEN DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ELSE CURDATE()
    END
WHERE id IN (301, 302, 303);

-- Ensure tasks table has the required columns
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS assigned_for ENUM('self','other') DEFAULT 'self';
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS followup_required BOOLEAN DEFAULT FALSE;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS planned_date DATE DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS contact_person VARCHAR(255) DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS project_name VARCHAR(255) DEFAULT NULL;

-- Update tasks table to add more followup-related data
UPDATE tasks SET 
    company_name = CASE id
        WHEN 102 THEN 'XYZ Technologies'
        WHEN 105 THEN 'ABC Corporation'
        WHEN 109 THEN 'Tech Solutions Inc'
        ELSE company_name
    END,
    contact_person = CASE id
        WHEN 102 THEN 'John Smith'
        WHEN 105 THEN 'Sarah Johnson'
        WHEN 109 THEN 'Mike Davis'
        ELSE contact_person
    END,
    contact_phone = CASE id
        WHEN 102 THEN '+1-555-0123'
        WHEN 105 THEN '+1-555-0456'
        WHEN 109 THEN '+1-555-0789'
        ELSE contact_phone
    END,
    project_name = CASE id
        WHEN 102 THEN 'Mobile App Development'
        WHEN 105 THEN 'Website Redesign'
        WHEN 109 THEN 'Training Program'
        ELSE project_name
    END
WHERE id IN (102, 105, 109);

-- Insert some additional users if they don't exist
INSERT IGNORE INTO users (id, name, email, password, role, employee_id, department_id, status, created_at) VALUES
(2, 'Jane Developer', 'jane@ergon.com', '$2y$10$example_hash', 'user', 'EMP002', 1, 'active', NOW()),
(3, 'Bob Manager', 'bob@ergon.com', '$2y$10$example_hash', 'admin', 'EMP003', 2, 'active', NOW()),
(4, 'Alice Marketing', 'alice@ergon.com', '$2y$10$example_hash', 'user', 'EMP004', 3, 'active', NOW()),
(5, 'Charlie Finance', 'charlie@ergon.com', '$2y$10$example_hash', 'user', 'EMP005', 4, 'active', NOW());

-- Insert departments if they don't exist
INSERT IGNORE INTO departments (id, name, description, status, created_at) VALUES
(1, 'Information Technology', 'Software development and system administration', 'active', NOW()),
(2, 'Human Resources', 'Employee management and organizational development', 'active', NOW()),
(3, 'Marketing', 'Brand promotion and customer engagement', 'active', NOW()),
(4, 'Finance', 'Financial planning and budget management', 'active', NOW());

-- Create followups table if it doesn't exist
CREATE TABLE IF NOT EXISTS followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    title VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    contact_person VARCHAR(255),
    contact_phone VARCHAR(20),
    project_name VARCHAR(255),
    follow_up_date DATE NOT NULL,
    original_date DATE,
    reminder_time TIME NULL,
    description TEXT,
    status ENUM('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    next_reminder DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_task_id (task_id),
    INDEX idx_follow_date (follow_up_date),
    INDEX idx_status (status)
);

-- Insert some follow-up entries
INSERT IGNORE INTO followups (user_id, task_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, reminder_time, description, status, created_at) VALUES
(1, 102, 'Follow-up: XYZ Project Status Check', 'XYZ Technologies', 'John Smith', '+1-555-0123', 'Mobile App Development', CURDATE(), '10:00:00', 'Check on project milestone completion and address any blockers', 'pending', NOW()),
(1, 105, 'Follow-up: ABC Corp Feedback Collection', 'ABC Corporation', 'Sarah Johnson', '+1-555-0456', 'Website Redesign', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'Collect feedback on delivered website redesign project', 'pending', NOW()),
(1, 109, 'Follow-up: Training Session Effectiveness', 'Tech Solutions Inc', 'Mike Davis', '+1-555-0789', 'Training Program', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Gather feedback on training effectiveness and areas for improvement', 'pending', NOW()))),
(4, 'Finance', 'Financial planning and budget management', 'active', NOW());

-- Add some recurring daily planner entries for the next few days
INSERT IGNORE INTO daily_planner (user_id, date, task_id, task_type, title, description, planned_start_time, planned_duration, priority_order, status, completion_status, created_at) VALUES
-- Today's entries
(1, CURDATE(), NULL, 'personal', 'Morning Email Review', 'Check and respond to overnight emails', '08:30:00', 30, 1, 'planned', 'not_started', NOW()),
(1, CURDATE(), NULL, 'personal', 'Daily Standup Meeting', 'Team synchronization meeting', '09:30:00', 30, 2, 'planned', 'not_started', NOW()),
(1, CURDATE(), NULL, 'personal', 'Feature Development', 'Work on new dashboard features', '10:00:00', 180, 3, 'planned', 'not_started', NOW()),
(1, CURDATE(), NULL, 'personal', 'Code Review Session', 'Review team members pull requests', '14:00:00', 90, 4, 'planned', 'not_started', NOW()),
(1, CURDATE(), NULL, 'personal', 'Documentation Update', 'Update technical documentation', '16:00:00', 60, 5, 'planned', 'not_started', NOW()),

-- Tomorrow's entries
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Sprint Planning', 'Plan next sprint with team', '09:00:00', 120, 1, 'planned', 'not_started', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Client Demo Preparation', 'Prepare demo for client presentation', '11:00:00', 90, 2, 'planned', 'not_started', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Bug Fixes', 'Address high priority bug reports', '14:30:00', 120, 3, 'planned', 'not_started', NOW()),

-- Day after tomorrow
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'personal', 'Performance Optimization', 'Optimize database queries and API calls', '10:00:00', 240, 1, 'planned', 'not_started', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'personal', 'Team One-on-Ones', 'Individual meetings with team members', '15:00:00', 120, 2, 'planned', 'not_started', NOW());

-- Add some completed tasks from yesterday
INSERT IGNORE INTO daily_planner (user_id, date, task_id, task_type, title, description, planned_start_time, planned_duration, priority_order, status, completion_status, actual_start_time, actual_end_time, notes, created_at) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'System Monitoring', 'Check server health and performance metrics', '08:00:00', 30, 1, 'planned', 'completed', '08:00:00', '08:25:00', 'All systems running smoothly', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Security Audit', 'Review security logs and access patterns', '09:00:00', 60, 2, 'planned', 'completed', '09:10:00', '10:00:00', 'No security issues found', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Feature Testing', 'Test new authentication features', '11:00:00', 120, 3, 'planned', 'completed', '11:15:00', '13:00:00', 'Found minor UI issues, created tickets', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'personal', 'Client Support', 'Handle customer support tickets', '14:00:00', 90, 4, 'planned', 'postponed', NULL, NULL, 'Postponed due to urgent bug fix', NOW());

-- Insert some follow-up entries in the followups table (if it exists)
INSERT IGNORE INTO followups (user_id, task_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, reminder_time, description, status, created_at) VALUES
(1, 102, 'Follow-up: XYZ Project Status Check', 'XYZ Technologies', 'John Smith', '+1-555-0123', 'Mobile App Development', CURDATE(), '10:00:00', 'Check on project milestone completion and address any blockers', 'pending', NOW()),
(1, 105, 'Follow-up: ABC Corp Feedback Collection', 'ABC Corporation', 'Sarah Johnson', '+1-555-0456', 'Website Redesign', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'Collect feedback on delivered website redesign project', 'pending', NOW()),
(3, 109, 'Follow-up: Training Session Effectiveness', 'Tech Solutions Inc', 'Mike Davis', '+1-555-0789', 'Training Program', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Gather feedback on training effectiveness and areas for improvement', 'pending', NOW());