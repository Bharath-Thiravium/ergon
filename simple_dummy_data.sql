-- Simple dummy data for tasks only

-- Insert dummy tasks with follow-up data
INSERT INTO tasks (title, description, assigned_to, assigned_by, department_id, task_category, status, priority, deadline, followup_required, company_name, contact_person, contact_phone, project_name, followup_date, followup_time, created_at) VALUES
('Follow-up with ABC Corp', 'Discuss project timeline and deliverables', 1, 1, 13, 'Payment Follow-up', 'assigned', 'high', '2024-12-20', 1, 'ABC Corporation', 'John Smith', '+91-9876543210', 'ERP Implementation', '2024-12-18', '10:00:00', NOW()),
('Client meeting for XYZ Ltd', 'Present quarterly report and discuss next phase', 1, 1, 15, 'Client Meeting', 'in_progress', 'medium', '2024-12-22', 1, 'XYZ Limited', 'Sarah Johnson', '+91-9876543211', 'Digital Marketing Campaign', '2024-12-19', '14:30:00', NOW()),
('GST filing reminder', 'Complete GST return filing for Q3', 1, 1, 13, 'GST Filing', 'assigned', 'high', '2024-12-25', 0, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
('Website development review', 'Review progress on company website redesign', 1, 1, 14, 'Development', 'in_progress', 'medium', '2024-12-21', 1, 'Tech Solutions Inc', 'Mike Wilson', '+91-9876543212', 'Website Redesign', '2024-12-20', '11:00:00', NOW()),
('Document collection', 'Collect pending documents from client', 1, 1, 6, 'Document Collection', 'assigned', 'high', '2024-12-19', 1, 'Global Enterprises', 'Lisa Brown', '+91-9876543213', 'License Renewal', '2024-12-18', '09:30:00', NOW()),
('Lead generation campaign', 'Launch new lead generation campaign', 1, 1, 15, 'Lead Generation', 'assigned', 'medium', '2024-12-27', 1, 'Marketing Pro', 'David Lee', '+91-9876543214', 'Q4 Campaign', '2024-12-21', '15:00:00', NOW());

-- Update tasks with some progress
UPDATE tasks SET progress = 25, status = 'in_progress' WHERE title = 'Follow-up with ABC Corp';
UPDATE tasks SET progress = 75, status = 'in_progress' WHERE title = 'Client meeting for XYZ Ltd';
UPDATE tasks SET progress = 50, status = 'in_progress' WHERE title = 'Website development review';