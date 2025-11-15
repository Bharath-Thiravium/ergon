-- Dummy Data for ERGON Database (excluding users table)

-- Advances
INSERT INTO `advances` (`user_id`, `type`, `amount`, `reason`, `requested_date`, `repayment_months`, `status`, `approved_by`, `approved_at`) VALUES
(1, 'Salary Advance', 5000.00, 'Medical emergency', '2025-11-01', 3, 'approved', 2, '2025-11-02 10:30:00'),
(2, 'Travel Advance', 2500.00, 'Business trip to Mumbai', '2025-11-05', 1, 'pending', NULL, NULL),
(16, 'General Advance', 3000.00, 'Personal expenses', '2025-11-10', 2, 'rejected', 1, '2025-11-11 14:20:00');

-- Attendance
INSERT INTO `attendance` (`user_id`, `check_in`, `check_out`, `location_name`, `status`) VALUES
(1, '2025-11-15 09:00:00', '2025-11-15 18:00:00', 'Main Office', 'present'),
(2, '2025-11-15 09:15:00', '2025-11-15 17:45:00', 'Main Office', 'present'),
(16, '2025-11-15 08:45:00', '2025-11-15 18:30:00', 'Main Office', 'present'),
(1, '2025-11-14 09:30:00', '2025-11-14 18:15:00', 'Main Office', 'late'),
(2, '2025-11-14 09:00:00', '2025-11-14 18:00:00', 'Main Office', 'present');

-- Daily Planner
INSERT INTO `daily_planner` (`user_id`, `department_id`, `plan_date`, `title`, `description`, `priority`, `estimated_hours`, `actual_hours`, `completion_percentage`, `completion_status`, `reminder_time`, `notes`) VALUES
(1, 1, '2025-11-15', 'Review quarterly reports', 'Analyze Q4 performance metrics', 'high', 3.00, 2.50, 85, 'in_progress', '09:00:00', 'Focus on revenue analysis'),
(2, 13, '2025-11-15', 'Update client invoices', 'Process pending invoices for November', 'medium', 2.00, NULL, 0, 'pending', '10:00:00', NULL),
(16, 13, '2025-11-15', 'Bank reconciliation', 'Reconcile October bank statements', 'high', 4.00, 3.75, 100, 'completed', '08:30:00', 'All discrepancies resolved'),
(1, 1, '2025-11-16', 'Team meeting preparation', 'Prepare agenda for monthly team meeting', 'medium', 1.50, NULL, 0, 'pending', '14:00:00', NULL);

-- Expenses
INSERT INTO `expenses` (`user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `expense_date`) VALUES
(1, 'Travel', 1200.00, 'Flight tickets for client meeting', NULL, 'approved', '2025-11-10'),
(2, 'Office Supplies', 350.00, 'Stationery and printing materials', NULL, 'pending', '2025-11-12'),
(16, 'Meals', 450.00, 'Client lunch meeting', NULL, 'approved', '2025-11-08'),
(1, 'Accommodation', 2500.00, 'Hotel stay for conference', NULL, 'pending', '2025-11-13');

-- Leaves
INSERT INTO `leaves` (`user_id`, `leave_type`, `start_date`, `end_date`, `days_requested`, `reason`, `status`) VALUES
(1, 'Annual Leave', '2025-12-20', '2025-12-25', 6, 'Christmas vacation', 'Pending'),
(2, 'Sick Leave', '2025-11-18', '2025-11-19', 2, 'Fever and cold', 'Approved'),
(16, 'Casual Leave', '2025-11-22', '2025-11-22', 1, 'Personal work', 'Pending');

-- Projects
INSERT INTO `projects` (`name`, `description`, `department_id`, `status`) VALUES
('ERP System Upgrade', 'Upgrading existing ERP system with new modules', 14, 'active'),
('Client Portal Development', 'Developing web portal for client interactions', 14, 'active'),
('Financial Audit 2025', 'Annual financial audit and compliance check', 13, 'active'),
('Marketing Campaign Q1', 'Digital marketing campaign for Q1 2026', 15, 'planning');

-- Settings
INSERT INTO `settings` (`company_name`, `logo_path`, `base_location_lat`, `base_location_lng`, `attendance_radius`, `backup_email`) VALUES
('Athenas Technologies', '/uploads/logo.png', 12.9716, 77.5946, 200, 'backup@athenas.co.in');

-- Followup History
INSERT INTO `followup_history` (`followup_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`) VALUES
(5, 'completed', 'pending', 'completed', 'Follow-up completed successfully', 2),
(1, 'created', NULL, 'Follow-up created', 'Initial creation of follow-up', 1),
(2, 'created', NULL, 'Follow-up created', 'Initial creation of follow-up', 2),
(3, 'created', NULL, 'Follow-up created', 'Initial creation of follow-up', 16),
(4, 'created', NULL, 'Follow-up created', 'Initial creation of follow-up', 1);

-- Tasks (without explicit IDs to avoid conflicts)
INSERT INTO `tasks` (`title`, `description`, `assigned_by`, `assigned_to`, `task_type`, `priority`, `deadline`, `progress`, `status`, `sla_hours`, `department_id`, `task_category`, `planned_date`, `company_name`, `contact_person`, `contact_phone`, `project_name`) VALUES
('Database Backup Setup', 'Configure automated daily database backups', 1, 2, 'ad-hoc', 'high', '2025-11-20 17:00:00', 60, 'in_progress', 48, 14, 'System Administration', '2025-11-15', NULL, NULL, NULL, 'ERP System Upgrade'),
('Invoice Template Update', 'Update invoice templates with new company branding', 2, 16, 'ad-hoc', 'medium', '2025-11-18 12:00:00', 0, 'assigned', 24, 13, 'Invoice Creation', '2025-11-16', NULL, NULL, NULL, NULL),
('Client Presentation Prep', 'Prepare presentation for new client onboarding', 1, 2, 'milestone', 'high', '2025-11-17 15:00:00', 25, 'in_progress', 36, 15, 'Client Presentation', '2025-11-15', 'TechCorp Solutions', 'John Smith', '9876543210', 'Client Portal Development'),
('GST Return Filing', 'File monthly GST returns for October', 16, 16, 'ad-hoc', 'high', '2025-11-16 18:00:00', 80, 'in_progress', 12, 13, 'GST Filing', '2025-11-15', NULL, NULL, NULL, NULL),
('System Security Audit', 'Conduct security audit of all systems', 1, 2, 'checklist', 'medium', '2025-11-25 17:00:00', 0, 'assigned', 72, 14, 'Security Implementation', '2025-11-18', NULL, NULL, NULL, 'ERP System Upgrade');

-- Followups
INSERT INTO `followups` (`user_id`, `title`, `description`, `company_name`, `contact_person`, `contact_phone`, `project_name`, `follow_up_date`, `original_date`, `reminder_time`, `status`, `priority`) VALUES
(1, 'Client Payment Follow-up', 'Follow up on pending payment from ABC Corp', 'ABC Corporation', 'John Doe', '9876543210', 'Website Development', '2025-11-15', '2025-11-15', '10:00:00', 'pending', 'high'),
(2, 'Project Status Update', 'Get status update on mobile app development', 'XYZ Tech', 'Jane Smith', '9876543211', 'Mobile App Project', '2025-11-16', '2025-11-16', '14:00:00', 'pending', 'medium'),
(16, 'Invoice Submission', 'Submit monthly invoices to client', 'Tech Solutions Ltd', 'Mike Johnson', '9876543212', 'Consulting Services', '2025-11-17', '2025-11-17', '09:30:00', 'pending', 'high'),
(1, 'Contract Renewal Discussion', 'Discuss contract renewal terms', 'Global Systems', 'Sarah Wilson', '9876543213', 'Support Contract', '2025-11-18', '2025-11-18', '11:00:00', 'pending', 'medium'),
(2, 'Completed Follow-up', 'This follow-up was completed successfully', 'Demo Company', 'Test Contact', '9876543214', 'Test Project', '2025-11-10', '2025-11-10', '15:00:00', 'completed', 'low');