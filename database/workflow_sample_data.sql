-- ERGON Workflow Sample Data
-- This script only clears sample/demo data - no new data insertion

-- Clear existing sample data
DELETE FROM tasks WHERE title LIKE '%Sample%' OR title LIKE '%Demo%';

-- Verify existing data
SELECT 'Existing Data Summary' as message;

SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM tasks;