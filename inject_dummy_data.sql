-- Inject dummy data for testing all modules

-- Insert dummy tasks with follow-up data
INSERT INTO tasks (title, description, assigned_to, assigned_by, department_id, task_category, status, priority, deadline, followup_required, company_name, contact_person, contact_phone, project_name, followup_date, followup_time, created_at) VALUES
('Follow-up with ABC Corp', 'Discuss project timeline and deliverables', 1, 1, 13, 'Payment Follow-up', 'assigned', 'high', '2024-12-20', 1, 'ABC Corporation', 'John Smith', '+91-9876543210', 'ERP Implementation', '2024-12-18', '10:00:00', NOW()),
('Client meeting for XYZ Ltd', 'Present quarterly report and discuss next phase', 1, 1, 15, 'Client Meeting', 'in_progress', 'medium', '2024-12-22', 1, 'XYZ Limited', 'Sarah Johnson', '+91-9876543211', 'Digital Marketing Campaign', '2024-12-19', '14:30:00', NOW()),
('GST filing reminder', 'Complete GST return filing for Q3', 1, 1, 13, 'GST Filing', 'assigned', 'high', '2024-12-25', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
('Website development review', 'Review progress on company website redesign', 1, 1, 14, 'Development', 'in_progress', 'medium', '2024-12-21', 1, 'Tech Solutions Inc', 'Mike Wilson', '+91-9876543212', 'Website Redesign', '2024-12-20', '11:00:00', NOW()),
('Recruitment interview', 'Interview candidates for HR manager position', 1, 1, 1, 'Recruitment', 'assigned', 'medium', '2024-12-23', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
('Document collection', 'Collect pending documents from client', 1, 1, 6, 'Document Collection', 'assigned', 'high', '2024-12-19', 1, 'Global Enterprises', 'Lisa Brown', '+91-9876543213', 'License Renewal', '2024-12-18', '09:30:00', NOW()),
('Vendor payment processing', 'Process pending vendor payments', 1, 1, 13, 'Vendor Payment', 'assigned', 'medium', '2024-12-24', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
('System maintenance', 'Perform monthly server maintenance', 1, 1, 14, 'Maintenance', 'assigned', 'low', '2024-12-26', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
('Lead generation campaign', 'Launch new lead generation campaign', 1, 1, 15, 'Lead Generation', 'assigned', 'medium', '2024-12-27', 1, 'Marketing Pro', 'David Lee', '+91-9876543214', 'Q4 Campaign', '2024-12-21', '15:00:00', NOW()),
('Quality audit', 'Conduct monthly quality audit', 1, 1, 5, 'Quality Control', 'assigned', 'medium', '2024-12-28', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW());

-- Insert dummy daily planner entries (skip if table doesn't exist)
INSERT IGNORE INTO daily_planner (user_id, plan_date, title, description, completion_status, created_at) VALUES
(1, CURDATE(), 'Morning standup meeting', 'Daily team standup to discuss progress', 'completed', NOW()),
(1, CURDATE(), 'Review client proposals', 'Review and approve pending client proposals', 'in_progress', NOW()),
(1, CURDATE(), 'Follow-up calls', 'Make follow-up calls to pending clients', 'not_started', NOW()),
(1, CURDATE(), 'Team meeting', 'Weekly team meeting to discuss project status', 'not_started', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Client presentation', 'Present project proposal to new client', 'not_started', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Code review session', 'Review code changes with development team', 'not_started', NOW());

-- Insert dummy evening updates (skip if table doesn't exist)
INSERT IGNORE INTO evening_updates (user_id, accomplishments, challenges, tomorrow_plan, overall_productivity, planner_date, created_at) VALUES
(1, 'Completed 3 client calls, finished proposal review, resolved 2 technical issues', 'Faced delays in getting client approvals, server maintenance took longer than expected', 'Focus on new client presentation, complete pending documentation, follow up with delayed projects', 8, CURDATE(), NOW()),
(1, 'Finished development tasks, conducted team meeting, updated project documentation', 'Network connectivity issues affected productivity, waiting for client feedback on designs', 'Client presentation preparation, code review session, start new feature development', 7, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NOW());

-- Insert dummy attendance records (skip if table doesn't exist)
INSERT IGNORE INTO attendance (user_id, date, clock_in_time, clock_out_time, total_hours, status, created_at) VALUES
(1, CURDATE(), '09:15:00', NULL, NULL, 'present', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '18:30:00', '09:30:00', 'present', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:10:00', '18:00:00', '08:50:00', 'present', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:30:00', '17:45:00', '08:15:00', 'late', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Insert dummy leave requests (skip if table doesn't exist)
INSERT IGNORE INTO leaves (user_id, leave_type, start_date, end_date, days_count, reason, status, applied_date, created_at) VALUES
(1, 'sick', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1, 'Medical appointment', 'pending', CURDATE(), NOW()),
(1, 'casual', DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 3, 'Family function', 'approved', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert dummy expense claims (skip if table doesn't exist)
INSERT IGNORE INTO expenses (user_id, expense_type, amount, description, expense_date, receipt_path, status, submitted_date, created_at) VALUES
(1, 'travel', 1500.00, 'Client visit travel expenses', DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'pending', CURDATE(), NOW()),
(1, 'meal', 800.00, 'Client lunch meeting', DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, 'approved', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'office_supplies', 2500.00, 'Laptop accessories and stationery', DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'approved', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert dummy advance requests (skip if table doesn't exist)
INSERT IGNORE INTO advances (user_id, amount, reason, request_date, required_date, status, created_at) VALUES
(1, 25000.00, 'Medical emergency - family member hospitalization', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'pending', NOW()),
(1, 15000.00, 'House rent advance payment', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'approved', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Update tasks with some progress and status changes
UPDATE tasks SET progress = 25, status = 'in_progress' WHERE title = 'Follow-up with ABC Corp';
UPDATE tasks SET progress = 75, status = 'in_progress' WHERE title = 'Client meeting for XYZ Ltd';
UPDATE tasks SET progress = 100, status = 'completed' WHERE title = 'Recruitment interview';
UPDATE tasks SET progress = 50, status = 'in_progress' WHERE title = 'Website development review';

-- Ensure all tasks are assigned to user 1
UPDATE tasks SET assigned_to = 1 WHERE assigned_to IS NULL;