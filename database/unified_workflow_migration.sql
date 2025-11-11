-- Unified Workflow Migration Script
-- This script updates existing tables to work with the unified workflow

-- Update tasks table to include assigned_for field
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS assigned_for ENUM('self','other') DEFAULT 'self';
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS followup_required BOOLEAN DEFAULT FALSE;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS planned_date DATE DEFAULT NULL;

-- Update daily_planner table to reference tasks
ALTER TABLE daily_planner ADD COLUMN IF NOT EXISTS completion_status ENUM('not_started','in_progress','completed','postponed') DEFAULT 'not_started';
ALTER TABLE daily_planner ADD COLUMN IF NOT EXISTS actual_start_time TIME DEFAULT NULL;
ALTER TABLE daily_planner ADD COLUMN IF NOT EXISTS actual_end_time TIME DEFAULT NULL;
ALTER TABLE daily_planner ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL;

-- Update evening_updates table to link with daily planner
ALTER TABLE evening_updates ADD COLUMN IF NOT EXISTS planner_date DATE DEFAULT NULL;
ALTER TABLE evening_updates ADD COLUMN IF NOT EXISTS overall_productivity INT DEFAULT 0;

-- Create task_calendar_view for calendar functionality
CREATE OR REPLACE VIEW task_calendar_view AS
SELECT 
    t.id,
    t.title,
    t.description,
    t.assigned_to,
    t.assigned_by,
    t.priority,
    t.status,
    t.progress,
    t.planned_date as date,
    t.deadline,
    u.name as assigned_user,
    'task' as entry_type
FROM tasks t
LEFT JOIN users u ON t.assigned_to = u.id
WHERE t.planned_date IS NOT NULL

UNION ALL

SELECT 
    dp.id,
    dp.title,
    dp.description,
    dp.user_id as assigned_to,
    dp.user_id as assigned_by,
    'medium' as priority,
    dp.status,
    0 as progress,
    dp.date,
    dp.date as deadline,
    u.name as assigned_user,
    'planner' as entry_type
FROM daily_planner dp
LEFT JOIN users u ON dp.user_id = u.id;

-- Create followup_tasks_view for followup filtering
CREATE OR REPLACE VIEW followup_tasks_view AS
SELECT 
    t.id,
    t.title,
    t.description,
    t.assigned_to,
    t.priority,
    t.status,
    t.task_category,
    t.created_at,
    u.name as assigned_user
FROM tasks t
LEFT JOIN users u ON t.assigned_to = u.id
WHERE t.followup_required = TRUE 
   OR t.task_category LIKE '%follow%'
   OR t.title LIKE '%follow%';