-- Add test task for today's date to verify calendar functionality
INSERT INTO tasks (title, description, assigned_by, assigned_to, planned_date, priority, status, created_at)
VALUES ('Test Task for Today - Calendar Fix', 'This is a test task to verify calendar functionality', 1, 1, CURDATE(), 'high', 'assigned', NOW());

-- Add daily plan for today
INSERT INTO daily_plans (user_id, plan_date, title, description, priority, status, created_at)
VALUES (1, CURDATE(), 'Daily Plan for Today - Calendar Test', 'Test daily plan entry for calendar verification', 'medium', 'pending', NOW());

-- Check what we have for current month
SELECT 'Tasks for current month:' as info;
SELECT t.id, t.title, t.planned_date, t.priority, t.status 
FROM tasks t 
WHERE MONTH(t.planned_date) = MONTH(CURDATE()) AND YEAR(t.planned_date) = YEAR(CURDATE());

SELECT 'Daily plans for current month:' as info;
SELECT dp.id, dp.title, dp.plan_date, dp.priority, dp.status 
FROM daily_plans dp 
WHERE MONTH(dp.plan_date) = MONTH(CURDATE()) AND YEAR(dp.plan_date) = YEAR(CURDATE());