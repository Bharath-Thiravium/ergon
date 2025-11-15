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

-- Evening Updates
INSERT INTO `evening_updates` (`user_id`, `date`, `planner_id`, `progress_percentage`, `actual_hours_spent`, `completion_status`, `blockers`, `notes`) VALUES
(1, '2025-11-14', 1, 85, 2.50, 'in_progress', NULL, 'Made good progress on revenue analysis'),
(16, '2025-11-14', 3, 100, 3.75, 'completed', NULL, 'Successfully completed bank reconciliation'),
(2, '2025-11-14', 2, 25, 0.50, 'in_progress', 'Waiting for client data', 'Need updated client information');

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

-- Tasks (insert with specific IDs)
INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_by`, `assigned_to`, `task_type`, `priority`, `deadline`, `progress`, `status`, `sla_hours`, `department_id`, `task_category`, `planned_date`, `company_name`, `contact_person`, `contact_phone`, `project_name`) VALUES
(143, 'Database Backup Setup', 'Configure automated daily database backups', 1, 2, 'ad-hoc', 'high', '2025-11-20 17:00:00', 60, 'in_progress', 48, 14, 'System Administration', '2025-11-15', NULL, NULL, NULL, 'ERP System Upgrade'),
(144, 'Invoice Template Update', 'Update invoice templates with new company branding', 2, 16, 'ad-hoc', 'medium', '2025-11-18 12:00:00', 0, 'assigned', 24, 13, 'Invoice Creation', '2025-11-16', NULL, NULL, NULL, NULL),
(145, 'Client Presentation Prep', 'Prepare presentation for new client onboarding', 1, 2, 'milestone', 'high', '2025-11-17 15:00:00', 25, 'in_progress', 36, 15, 'Client Presentation', '2025-11-15', 'TechCorp Solutions', 'John Smith', '9876543210', 'Client Portal Development'),
(146, 'GST Return Filing', 'File monthly GST returns for October', 16, 16, 'ad-hoc', 'high', '2025-11-16 18:00:00', 80, 'in_progress', 12, 13, 'GST Filing', '2025-11-15', NULL, NULL, NULL, NULL),
(147, 'System Security Audit', 'Conduct security audit of all systems', 1, 2, 'checklist', 'medium', '2025-11-25 17:00:00', 0, 'assigned', 72, 14, 'Security Implementation', '2025-11-18', NULL, NULL, NULL, 'ERP System Upgrade');

-- Daily Tasks table
CREATE TABLE IF NOT EXISTS `daily_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `task_id` INT NULL,
    `scheduled_date` DATE NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `planned_start_time` TIME NULL,
    `planned_duration` INT DEFAULT 60,
    `priority` ENUM('low','medium','high') DEFAULT 'medium',
    `status` ENUM('not_started','in_progress','paused','completed','postponed') DEFAULT 'not_started',
    `start_time` TIMESTAMP NULL,
    `pause_time` TIMESTAMP NULL,
    `resume_time` TIMESTAMP NULL,
    `completion_time` TIMESTAMP NULL,
    `active_seconds` INT DEFAULT 0,
    `completed_percentage` INT DEFAULT 0,
    `postponed_from_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, scheduled_date),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- Daily Tasks data
INSERT INTO `daily_tasks` (`user_id`, `task_id`, `scheduled_date`, `title`, `description`, `planned_start_time`, `planned_duration`, `priority`, `status`, `active_seconds`, `completed_percentage`) VALUES
(1, 143, '2025-11-15', 'Database Backup Setup', 'Configure automated daily database backups', '09:00:00', 120, 'high', 'in_progress', 3600, 60),
(2, 145, '2025-11-15', 'Client Presentation Prep', 'Prepare presentation for new client onboarding', '10:30:00', 180, 'high', 'paused', 1800, 25),
(16, 146, '2025-11-15', 'GST Return Filing', 'File monthly GST returns for October', '08:30:00', 240, 'high', 'in_progress', 7200, 80),
(1, NULL, '2025-11-15', 'Team Standup Meeting', 'Daily team coordination meeting', '11:00:00', 30, 'medium', 'completed', 1800, 100),
(2, 144, '2025-11-16', 'Invoice Template Update', 'Update invoice templates with new company branding', '09:30:00', 90, 'medium', 'not_started', 0, 0);

-- Time Logs table
CREATE TABLE IF NOT EXISTS `time_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `daily_task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action` ENUM('start','pause','resume','complete','postpone') NOT NULL,
    `timestamp` TIMESTAMP NOT NULL,
    `active_duration` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (daily_task_id) REFERENCES daily_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Time Logs data
INSERT INTO `time_logs` (`daily_task_id`, `user_id`, `action`, `timestamp`, `active_duration`) VALUES
(1, 1, 'start', '2025-11-15 09:00:00', 0),
(1, 1, 'pause', '2025-11-15 10:00:00', 3600),
(2, 2, 'start', '2025-11-15 10:30:00', 0),
(2, 2, 'pause', '2025-11-15 11:00:00', 1800),
(3, 16, 'start', '2025-11-15 08:30:00', 0),
(4, 1, 'start', '2025-11-15 11:00:00', 0),
(4, 1, 'complete', '2025-11-15 11:30:00', 1800);

-- Daily Performance table
CREATE TABLE IF NOT EXISTS `daily_performance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `total_planned_minutes` INT DEFAULT 0,
    `total_active_minutes` DECIMAL(8,2) DEFAULT 0,
    `total_tasks` INT DEFAULT 0,
    `completed_tasks` INT DEFAULT 0,
    `in_progress_tasks` INT DEFAULT 0,
    `postponed_tasks` INT DEFAULT 0,
    `completion_percentage` DECIMAL(5,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Daily Performance data
INSERT INTO `daily_performance` (`user_id`, `date`, `total_planned_minutes`, `total_active_minutes`, `total_tasks`, `completed_tasks`, `in_progress_tasks`, `postponed_tasks`, `completion_percentage`) VALUES
(1, '2025-11-15', 150, 90.00, 2, 1, 1, 0, 50.00),
(2, '2025-11-15', 180, 30.00, 1, 0, 1, 0, 0.00),
(16, '2025-11-15', 240, 120.00, 1, 0, 1, 0, 0.00),
(1, '2025-11-14', 180, 165.00, 2, 1, 1, 0, 50.00),
(2, '2025-11-14', 120, 90.00, 2, 1, 1, 0, 50.00);