-- Clean All Dummy Data from ERGON System
-- This script removes all sample, demo, test, and dummy data

-- Clean dummy tasks
DELETE FROM tasks WHERE 
    title LIKE '%Sample%' OR 
    title LIKE '%Demo%' OR 
    title LIKE '%Test%' OR 
    title LIKE '%Dummy%' OR
    title LIKE '%Example%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%' OR
    project_name LIKE '%Sample%' OR
    project_name LIKE '%Demo%' OR
    project_name LIKE '%Test%';

-- Clean dummy projects
DELETE FROM projects WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%' OR
    description LIKE '%sample%' OR
    description LIKE '%demo%' OR
    description LIKE '%test%';

-- Clean dummy users (keep admin)
DELETE FROM users WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%' AND
    email != 'admin@ergon.com';

-- Clean dummy departments
DELETE FROM departments WHERE 
    name LIKE '%Sample%' OR 
    name LIKE '%Demo%' OR 
    name LIKE '%Test%' OR 
    name LIKE '%Dummy%' OR
    name LIKE '%Example%';

-- Skip non-existent tables (daily_tasks, daily_performance)

-- Verify cleanup
SELECT 'Cleanup Complete' as message;
SELECT COUNT(*) as remaining_tasks FROM tasks;
SELECT COUNT(*) as remaining_projects FROM projects;
SELECT COUNT(*) as remaining_users FROM users;