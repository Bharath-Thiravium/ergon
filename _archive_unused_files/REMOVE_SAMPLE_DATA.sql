-- REMOVE ALL SAMPLE DATA FROM ERGON TASK MANAGEMENT SYSTEM
-- Run this script in phpMyAdmin to clean all sample/demo data

-- 1. Remove sample tasks
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

-- 2. Remove sample projects (if table exists)
DELETE FROM projects WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%';

-- 3. Remove sample users (keep admin and real users)
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
    email != 'admin@ergon.com' AND
    email != 'owner@ergon.com';

-- 4. Remove sample departments
DELETE FROM departments WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%';

-- 5. Remove sample followups
DELETE FROM followups WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR 
    company_name LIKE '%Sample%' OR
    company_name LIKE '%Demo%' OR
    company_name LIKE '%Test%';

-- 6. Remove sample notifications
DELETE FROM notifications WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR
    message LIKE '%sample%' OR
    message LIKE '%demo%' OR
    message LIKE '%test%';

-- 7. Remove sample attendance records (keep real ones)
DELETE FROM attendance WHERE 
    location_name LIKE '%Sample%' OR
    location_name LIKE '%Demo%' OR
    location_name LIKE '%Test%';

-- 8. Clean up orphaned records
DELETE FROM followups WHERE task_id NOT IN (SELECT id FROM tasks);
DELETE FROM notifications WHERE user_id NOT IN (SELECT id FROM users);

-- 9. Reset auto-increment counters (optional)
-- ALTER TABLE tasks AUTO_INCREMENT = 1;
-- ALTER TABLE users AUTO_INCREMENT = 1;
-- ALTER TABLE departments AUTO_INCREMENT = 1;
-- ALTER TABLE followups AUTO_INCREMENT = 1;

-- 10. Verification queries
SELECT 'CLEANUP VERIFICATION' as status;
SELECT COUNT(*) as remaining_tasks FROM tasks;
SELECT COUNT(*) as remaining_users FROM users;
SELECT COUNT(*) as remaining_departments FROM departments;
SELECT COUNT(*) as remaining_followups FROM followups;
SELECT COUNT(*) as remaining_notifications FROM notifications;

-- Show remaining data for review
SELECT 'REMAINING TASKS:' as info;
SELECT id, title, assigned_to, status, created_at FROM tasks ORDER BY created_at DESC LIMIT 10;

SELECT 'REMAINING USERS:' as info;
SELECT id, name, email, role FROM users ORDER BY created_at DESC;