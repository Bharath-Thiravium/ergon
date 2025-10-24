-- Database Performance Optimization
-- Add critical indexes for faster queries

-- Users table indexes
ALTER TABLE users ADD INDEX idx_users_role_status (role, status);
ALTER TABLE users ADD INDEX idx_users_email (email);
ALTER TABLE users ADD INDEX idx_users_employee_id (employee_id);

-- Attendance table indexes
ALTER TABLE attendance ADD INDEX idx_attendance_user_date (user_id, date);
ALTER TABLE attendance ADD INDEX idx_attendance_date (date);

-- Leaves table indexes
ALTER TABLE leaves ADD INDEX idx_leaves_user_status (user_id, status);
ALTER TABLE leaves ADD INDEX idx_leaves_status_date (status, start_date);

-- Expenses table indexes
ALTER TABLE expenses ADD INDEX idx_expenses_user_status (user_id, status);
ALTER TABLE expenses ADD INDEX idx_expenses_status (status);

-- Tasks table indexes (if exists)
ALTER TABLE tasks ADD INDEX idx_tasks_assigned_status (assigned_to, status);
ALTER TABLE tasks ADD INDEX idx_tasks_due_date (due_date);

-- Daily planner indexes
ALTER TABLE daily_planner ADD INDEX idx_planner_user_date (user_id, plan_date);
ALTER TABLE daily_planner ADD INDEX idx_planner_status (completion_status);

-- Optimize table storage
OPTIMIZE TABLE users, attendance, leaves, expenses, tasks, daily_planner, departments, settings;