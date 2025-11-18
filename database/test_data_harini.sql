-- Test Data for harini@athenas.co.in
-- This script creates comprehensive test data for all modules

-- Get Harini's user ID
SET @harini_id = (SELECT id FROM users WHERE email = 'harini@athenas.co.in' LIMIT 1);
SET @admin_id = (SELECT id FROM users WHERE role IN ('admin', 'owner') LIMIT 1);

-- If Harini doesn't exist, create the user
INSERT IGNORE INTO users (name, email, password, role, status, created_at) 
VALUES ('Harini Kumar', 'harini@athenas.co.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NOW());

SET @harini_id = (SELECT id FROM users WHERE email = 'harini@athenas.co.in' LIMIT 1);

-- Ensure departments exist
INSERT IGNORE INTO departments (name, description, status) VALUES 
('Marketing', 'Marketing Department', 'active'),
('Sales', 'Sales Department', 'active'),
('IT', 'Information Technology', 'active');

SET @marketing_dept = (SELECT id FROM departments WHERE name = 'Marketing' LIMIT 1);
SET @sales_dept = (SELECT id FROM departments WHERE name = 'Sales' LIMIT 1);

-- Projects table was removed in migration, set project variables to NULL
SET @project1 = NULL;
SET @project2 = NULL;
SET @project3 = NULL;

-- Tasks with various statuses and priorities
INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, deadline, status, progress, sla_hours, task_category, created_at) VALUES 
('Complete Website Content Review', 'Review and update all website content for accuracy and SEO optimization', @admin_id, @harini_id, 'high', '2024-12-25 17:00:00', 'in_progress', 65, 48, 'Content Management', NOW() - INTERVAL 2 DAY),
('Social Media Campaign Setup', 'Set up social media campaigns for Q1 marketing push', @admin_id, @harini_id, 'medium', '2024-12-30 12:00:00', 'pending', 0, 24, 'Social Media', NOW() - INTERVAL 1 DAY),
('Customer Feedback Analysis', 'Analyze customer feedback from last quarter and prepare report', @admin_id, @harini_id, 'high', '2024-12-28 15:00:00', 'completed', 100, 16, 'Analysis', NOW() - INTERVAL 3 DAY),
('Email Newsletter Design', 'Design monthly email newsletter template', @admin_id, @harini_id, 'low', '2025-01-05 10:00:00', 'pending', 0, 8, 'Design', NOW()),
('Market Research Report', 'Conduct market research for new product launch', @admin_id, @harini_id, 'high', '2024-12-27 16:00:00', 'pending', 30, 72, 'Research', NOW() - INTERVAL 1 DAY),
('Blog Content Creation', 'Write 5 blog posts for company website', @admin_id, @harini_id, 'medium', '2025-01-10 14:00:00', 'pending', 20, 40, 'Content Creation', NOW()),
('Competitor Analysis', 'Analyze top 3 competitors and their strategies', @admin_id, @harini_id, 'high', '2024-12-26 11:00:00', 'pending', 0, 24, 'Analysis', NOW() - INTERVAL 2 DAY);

-- Daily Planner entries (using daily_planner table)
INSERT INTO daily_planner (user_id, plan_date, title, description, reminder_time, completion_status, created_at) VALUES 
(@harini_id, CURDATE(), 'Morning Email Review', 'Check and respond to priority emails', '09:00:00', 'completed', NOW()),
(@harini_id, CURDATE(), 'Content Strategy Meeting', 'Attend weekly content strategy meeting', '10:30:00', 'in_progress', NOW()),
(@harini_id, CURDATE(), 'Social Media Posts', 'Create and schedule social media posts', '14:00:00', 'pending', NOW()),
(@harini_id, CURDATE(), 'Website Analytics Review', 'Review website performance metrics', '15:30:00', 'pending', NOW()),
(@harini_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Client Presentation Prep', 'Prepare presentation for client meeting', '09:00:00', 'pending', NOW()),
(@harini_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Team Standup Meeting', 'Daily team standup and progress update', '11:00:00', 'pending', NOW());

-- Create contacts for follow-ups
INSERT IGNORE INTO contacts (name, phone, company) VALUES 
('John Smith', '+1-555-0123', 'ABC Corporation'),
('Sarah Johnson', '+1-555-0456', 'XYZ Marketing'),
('Mike Davis', '+1-555-0789', 'Tech Solutions Inc'),
('Lisa Wilson', '+1-555-0321', 'Global Enterprises'),
('Training Manager', 'ext-1234', 'Internal Team'),
('Tom Brown', '+1-555-0654', 'Creative Agency');

-- Follow-ups with various statuses
INSERT INTO followups (user_id, contact_id, title, description, follow_up_date, status, created_at) VALUES 
(@harini_id, (SELECT id FROM contacts WHERE name = 'John Smith' LIMIT 1), 'Follow up with ABC Corp', 'Discuss partnership opportunities and next steps', CURDATE(), 'pending', NOW()),
(@harini_id, (SELECT id FROM contacts WHERE name = 'Sarah Johnson' LIMIT 1), 'Client Feedback Collection', 'Collect feedback on recent campaign performance', CURDATE() + INTERVAL 1 DAY, 'pending', NOW()),
(@harini_id, (SELECT id FROM contacts WHERE name = 'Mike Davis' LIMIT 1), 'Product Demo Scheduling', 'Schedule product demonstration for potential client', CURDATE() - INTERVAL 1 DAY, 'completed', NOW() - INTERVAL 1 DAY),
(@harini_id, (SELECT id FROM contacts WHERE name = 'Lisa Wilson' LIMIT 1), 'Contract Renewal Discussion', 'Discuss contract renewal terms and conditions', CURDATE() + INTERVAL 3 DAY, 'pending', NOW()),
(@harini_id, (SELECT id FROM contacts WHERE name = 'Training Manager' LIMIT 1), 'Training Session Follow-up', 'Follow up on training effectiveness and feedback', CURDATE() - INTERVAL 2 DAY, 'pending', NOW()),
(@harini_id, (SELECT id FROM contacts WHERE name = 'Tom Brown' LIMIT 1), 'Vendor Meeting Rescheduling', 'Reschedule meeting with new vendor', CURDATE() + INTERVAL 2 DAY, 'pending', NOW());

-- Leave Applications (using leaves table)
INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status, created_at) VALUES 
(@harini_id, 'Annual Leave', '2024-12-30', '2025-01-02', 4, 'Year-end vacation with family', 'Approved', NOW() - INTERVAL 5 DAY),
(@harini_id, 'Sick Leave', '2024-12-20', '2024-12-20', 1, 'Medical appointment', 'Approved', NOW() - INTERVAL 8 DAY),
(@harini_id, 'Casual Leave', '2025-01-15', '2025-01-15', 1, 'Personal errands', 'Pending', NOW() - INTERVAL 1 DAY),
(@harini_id, 'Annual Leave', '2025-02-14', '2025-02-16', 3, 'Long weekend break', 'Pending', NOW());

-- Expense Claims (using expenses table)
INSERT INTO expenses (user_id, category, amount, description, expense_date, attachment, status, created_at) VALUES 
(@harini_id, 'Travel', 250.00, 'Client meeting travel expenses - taxi and parking', '2024-12-18', '/uploads/receipts/harini_travel_001.pdf', 'approved', NOW() - INTERVAL 6 DAY),
(@harini_id, 'Meals', 85.50, 'Business lunch with potential client', '2024-12-19', '/uploads/receipts/harini_meals_001.pdf', 'approved', NOW() - INTERVAL 5 DAY),
(@harini_id, 'Office Supplies', 45.75, 'Marketing materials and stationery', '2024-12-21', '/uploads/receipts/harini_supplies_001.pdf', 'pending', NOW() - INTERVAL 2 DAY),
(@harini_id, 'Software', 99.00, 'Monthly subscription for design software', '2024-12-22', '/uploads/receipts/harini_software_001.pdf', 'pending', NOW() - INTERVAL 1 DAY),
(@harini_id, 'Training', 350.00, 'Digital marketing certification course', '2024-12-15', '/uploads/receipts/harini_training_001.pdf', 'rejected', NOW() - INTERVAL 8 DAY);

-- Advance Requests (using advances table)
INSERT INTO advances (user_id, type, amount, reason, requested_date, status, approved_by, approved_at, created_at) VALUES 
(@harini_id, 'General Advance', 1000.00, 'Conference attendance and accommodation', NOW() - INTERVAL 10 DAY, 'approved', @admin_id, NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 10 DAY),
(@harini_id, 'Travel Advance', 500.00, 'Client entertainment and business development', NOW() - INTERVAL 3 DAY, 'pending', NULL, NULL, NOW() - INTERVAL 3 DAY),
(@harini_id, 'General Advance', 750.00, 'Training program and certification fees', NOW() - INTERVAL 15 DAY, 'rejected', @admin_id, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 15 DAY);

-- Attendance Records
INSERT INTO attendance (user_id, check_in, status, created_at) VALUES 
(@harini_id, CONCAT(CURDATE() - INTERVAL 5 DAY, ' 09:15:00'), 'present', NOW() - INTERVAL 5 DAY),
(@harini_id, CONCAT(CURDATE() - INTERVAL 4 DAY, ' 09:05:00'), 'present', NOW() - INTERVAL 4 DAY),
(@harini_id, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 09:30:00'), 'late', NOW() - INTERVAL 3 DAY),
(@harini_id, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 09:00:00'), 'present', NOW() - INTERVAL 2 DAY),
(@harini_id, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:10:00'), 'present', NOW() - INTERVAL 1 DAY);

SELECT 'Test data for Harini created successfully!' as message;

SELECT 'Test data inserted successfully for harini@athenas.co.in' as status;
SELECT CONCAT('User ID: ', @harini_id) as user_info;
SELECT 'Data includes: Tasks, Daily Tasks, Follow-ups, Leaves, Expenses, Advances, Attendance, Notifications, History' as modules_covered;