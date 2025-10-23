-- Dummy data for testing calendar, daily planner, and task management

-- Insert dummy users if they don't exist
INSERT IGNORE INTO users (id, name, email, password, role, department, status) VALUES 
(1, 'John Owner', 'owner@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 'Management', 'active'),
(2, 'Jane Admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT', 'active'),
(3, 'Bob User', 'user@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'IT', 'active'),
(4, 'Alice HR', 'hr@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'HR', 'active'),
(5, 'Mike Finance', 'finance@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Finance', 'active');

-- Insert dummy tasks
INSERT IGNORE INTO tasks (id, title, description, assigned_by, assigned_to, task_type, priority, deadline, progress, status) VALUES 
(1, 'Complete Project Documentation', 'Finalize all project documentation for Q4 release', 2, 3, 'milestone', 'high', '2024-01-15 17:00:00', 75, 'in_progress'),
(2, 'Review Security Protocols', 'Annual security audit and protocol review', 2, 3, 'checklist', 'urgent', '2024-01-10 12:00:00', 30, 'in_progress'),
(3, 'Prepare Monthly Reports', 'Generate and review monthly financial reports', 2, 5, 'timed', 'medium', '2024-01-20 15:00:00', 0, 'assigned'),
(4, 'Conduct Team Interviews', 'Interview candidates for developer position', 2, 4, 'ad-hoc', 'high', '2024-01-12 14:00:00', 50, 'in_progress'),
(5, 'System Backup Verification', 'Verify all system backups are working correctly', 2, 3, 'checklist', 'medium', '2024-01-25 10:00:00', 100, 'completed');

-- Insert dummy daily planner entries
INSERT IGNORE INTO daily_planners (user_id, department_id, plan_date, title, description, priority, estimated_hours, actual_hours, completion_status, completion_percentage, notes, reminder_time) VALUES 
-- Today's plans
(3, 2, CURDATE(), 'Code Review Session', 'Review pull requests and provide feedback', 'high', 2.0, 1.5, 'in_progress', 75, 'Reviewed 3 out of 4 PRs', '09:00:00'),
(3, 2, CURDATE(), 'Database Optimization', 'Optimize slow queries in production database', 'urgent', 3.0, 0, 'not_started', 0, NULL, '14:00:00'),
(4, 1, CURDATE(), 'Interview Candidates', 'Conduct interviews for marketing position', 'high', 4.0, 2.0, 'in_progress', 50, 'Completed 2 interviews', '10:30:00'),
(5, 3, CURDATE(), 'Monthly Reconciliation', 'Reconcile bank statements for December', 'medium', 2.5, 0, 'not_started', 0, NULL, '11:00:00'),

-- Yesterday's plans
(3, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Server Maintenance', 'Update server patches and restart services', 'high', 3.0, 3.5, 'completed', 100, 'All servers updated successfully', '08:00:00'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Employee Onboarding', 'Onboard new hire - prepare documents', 'medium', 2.0, 2.0, 'completed', 100, 'Onboarding completed', '09:30:00'),
(5, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Invoice Processing', 'Process pending vendor invoices', 'medium', 1.5, 1.0, 'completed', 100, 'Processed 15 invoices', '13:00:00'),

-- Tomorrow's plans
(3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Deploy New Features', 'Deploy latest features to production', 'urgent', 2.0, 0, 'not_started', 0, NULL, '09:00:00'),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Team Meeting', 'Weekly HR team sync meeting', 'low', 1.0, 0, 'not_started', 0, NULL, '15:00:00'),
(5, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Budget Review', 'Review Q1 budget allocations', 'high', 2.5, 0, 'not_started', 0, NULL, '10:00:00'),

-- Next week's plans
(3, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Security Audit', 'Conduct quarterly security audit', 'high', 4.0, 0, 'not_started', 0, NULL, '09:00:00'),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'Training Session', 'Conduct diversity and inclusion training', 'medium', 3.0, 0, 'not_started', 0, NULL, '14:00:00'),
(5, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Financial Analysis', 'Analyze Q4 financial performance', 'high', 3.5, 0, 'not_started', 0, NULL, '11:00:00');

-- Insert dummy task updates
INSERT IGNORE INTO task_updates (task_id, user_id, progress, comment, created_at) VALUES 
(1, 3, 25, 'Started working on API documentation', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 3, 50, 'Completed user guide section', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 3, 75, 'Working on technical specifications', NOW()),
(2, 3, 30, 'Reviewed current security protocols', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 4, 25, 'Scheduled interviews with 4 candidates', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 4, 50, 'Completed 2 interviews, 2 more pending', NOW()),
(5, 3, 100, 'All backup systems verified and working', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert dummy department form submissions
INSERT IGNORE INTO department_form_submissions (template_id, user_id, planner_id, form_data, submission_date, status) VALUES 
(2, 3, 1, '{"tickets_resolved": 5, "code_commits": 8}', CURDATE(), 'submitted'),
(1, 4, 3, '{"interviews_conducted": 2, "resumes_reviewed": 12}', CURDATE(), 'submitted'),
(3, 5, 4, '{"invoices_processed": 15, "payments_made": 8}', CURDATE(), 'submitted'),
(2, 3, 6, '{"tickets_resolved": 7, "code_commits": 12}', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'approved'),
(1, 4, 7, '{"interviews_conducted": 1, "resumes_reviewed": 8}', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'approved');

-- Insert dummy attendance records
INSERT IGNORE INTO attendance (user_id, check_in, check_out, latitude, longitude, location_name, status) VALUES 
(3, CONCAT(CURDATE(), ' 09:15:00'), CONCAT(CURDATE(), ' 18:30:00'), 28.6139, 77.2090, 'Office - IT Department', 'present'),
(4, CONCAT(CURDATE(), ' 09:00:00'), NULL, 28.6139, 77.2090, 'Office - HR Department', 'present'),
(5, CONCAT(CURDATE(), ' 09:30:00'), CONCAT(CURDATE(), ' 17:45:00'), 28.6139, 77.2090, 'Office - Finance Department', 'present'),
(3, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 18:00:00'), 28.6139, 77.2090, 'Office - IT Department', 'present'),
(4, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 09:15:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:30:00'), 28.6139, 77.2090, 'Office - HR Department', 'present');

-- Insert dummy leave requests
INSERT IGNORE INTO leaves (employee_id, type, start_date, end_date, reason, status, approved_by) VALUES 
(3, 'Sick Leave', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Medical appointment', 'Pending', NULL),
(4, 'Annual Leave', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'Family vacation', 'Approved', 2),
(5, 'Personal Leave', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Personal work', 'Pending', NULL);

-- Insert dummy expenses
INSERT IGNORE INTO expenses (user_id, category, amount, description, status, approved_by) VALUES 
(3, 'Travel', 250.00, 'Client meeting transportation', 'approved', 2),
(4, 'Training', 500.00, 'HR certification course', 'pending', NULL),
(5, 'Office Supplies', 75.50, 'Stationery and printing', 'approved', 2),
(3, 'Software', 99.00, 'Development tools subscription', 'pending', NULL);