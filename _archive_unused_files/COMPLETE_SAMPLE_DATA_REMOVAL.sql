-- COMPLETE SAMPLE DATA REMOVAL FOR ERGON TASK MANAGEMENT SYSTEM
-- This script removes ALL sample, demo, and test data from the system
-- Run this in phpMyAdmin after backing up your database

-- 1. Remove all sample tasks (including hardcoded ones from controller)
DELETE FROM tasks WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR 
    title LIKE '%Dummy%' OR
    title LIKE '%Example%' OR
    title = 'Database Setup' OR
    title = 'UI Design' OR
    title = 'API Development' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%' OR
    task_category LIKE '%Sample%' OR
    task_category LIKE '%Demo%' OR
    task_category LIKE '%Test%';

-- 2. Remove sample daily tasks
DELETE FROM daily_tasks WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR 
    title LIKE '%Dummy%' OR
    title LIKE '%Example%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%';

-- 3. Remove sample projects
DELETE FROM projects WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%';

-- 4. Remove sample users (preserve admin and real users)
DELETE FROM users WHERE 
    (name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%' OR
    name = 'John Doe' OR
    name = 'Jane Smith' OR
    name = 'Mike Johnson' OR
    name = 'System Owner' OR
    name = 'Admin User') AND
    email NOT IN ('admin@ergon.com', 'owner@ergon.com') AND
    role != 'owner';

-- 5. Remove sample departments
DELETE FROM departments WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%';

-- 6. Remove sample followups
DELETE FROM followups WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR 
    company_name LIKE '%Sample%' OR
    company_name LIKE '%Demo%' OR
    company_name LIKE '%Test%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%';

-- 7. Remove sample notifications
DELETE FROM notifications WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR
    message LIKE '%sample%' OR
    message LIKE '%demo%' OR
    message LIKE '%test%';

-- 8. Remove sample attendance records
DELETE FROM attendance WHERE 
    location_name LIKE '%Sample%' OR
    location_name LIKE '%Demo%' OR
    location_name LIKE '%Test%';

-- 9. Remove sample time logs
DELETE FROM time_logs WHERE 
    notes LIKE '%Sample%' OR
    notes LIKE '%Demo%' OR
    notes LIKE '%Test%';

-- 10. Clean up orphaned records
DELETE FROM followups WHERE task_id IS NOT NULL AND task_id NOT IN (SELECT id FROM tasks);
DELETE FROM daily_tasks WHERE task_id IS NOT NULL AND task_id NOT IN (SELECT id FROM tasks);
DELETE FROM time_logs WHERE task_id IS NOT NULL AND task_id NOT IN (SELECT id FROM tasks);
DELETE FROM notifications WHERE user_id NOT IN (SELECT id FROM users);

-- 11. Clean up daily performance records for removed users
DELETE FROM daily_performance WHERE user_id NOT IN (SELECT id FROM users);

-- 12. Reset auto-increment counters (optional - uncomment if needed)
-- ALTER TABLE tasks AUTO_INCREMENT = 1;
-- ALTER TABLE daily_tasks AUTO_INCREMENT = 1;
-- ALTER TABLE followups AUTO_INCREMENT = 1;
-- ALTER TABLE time_logs AUTO_INCREMENT = 1;

-- 13. Final verification
SELECT '=== CLEANUP VERIFICATION ===' as status;

SELECT 'Tasks Summary:' as info;
SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM tasks;

SELECT 'Users Summary:' as info;
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) as owners
FROM users;

SELECT 'Other Tables:' as info;
SELECT 
    (SELECT COUNT(*) FROM departments) as departments,
    (SELECT COUNT(*) FROM followups) as followups,
    (SELECT COUNT(*) FROM notifications) as notifications,
    (SELECT COUNT(*) FROM daily_tasks) as daily_tasks;

-- Show remaining data for review (first 5 records of each)
SELECT '=== REMAINING DATA PREVIEW ===' as status;

SELECT 'Recent Tasks:' as info;
SELECT id, title, assigned_to, status, created_at FROM tasks ORDER BY created_at DESC LIMIT 5;

SELECT 'Active Users:' as info;
SELECT id, name, email, role FROM users ORDER BY created_at DESC LIMIT 5;

SELECT 'Active Departments:' as info;
SELECT id, name FROM departments ORDER BY created_at DESC LIMIT 5;