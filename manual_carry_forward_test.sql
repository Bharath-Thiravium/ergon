-- Manual Carry Forward Test SQL
-- Replace USER_ID with actual user ID (e.g., 1)

-- 1. Check current pending tasks from past dates
SELECT id, title, planned_date, status, assigned_to 
FROM tasks 
WHERE assigned_to = 1 
AND status IN ('assigned', 'not_started') 
AND planned_date < CURDATE() 
AND planned_date IS NOT NULL;

-- 2. Carry forward pending tasks to today (replace 1 with actual user ID)
UPDATE tasks SET planned_date = CURDATE() 
WHERE assigned_to = 1 
AND status IN ('assigned', 'not_started') 
AND planned_date < CURDATE() 
AND planned_date IS NOT NULL;

-- 3. Verify tasks were moved
SELECT id, title, planned_date, status, assigned_to 
FROM tasks 
WHERE assigned_to = 1 
AND planned_date = CURDATE() 
AND status IN ('assigned', 'not_started');

-- 4. Check tasks that should appear in today's planner
SELECT id, title, planned_date, DATE(created_at) as created_date, status 
FROM tasks 
WHERE assigned_to = 1 
AND status != 'completed'
AND (
    planned_date = CURDATE() OR 
    (planned_date IS NULL AND DATE(created_at) = CURDATE())
)
ORDER BY 
    CASE WHEN planned_date IS NOT NULL THEN 1 ELSE 2 END,
    created_at DESC;