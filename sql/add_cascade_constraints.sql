-- Add foreign key constraints to ensure cascade deletion integrity
-- This script should be run to add proper database-level constraints

-- Add foreign key constraint for followups.task_id -> tasks.id
ALTER TABLE followups 
ADD CONSTRAINT fk_followups_task_id 
FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE;

-- Add foreign key constraint for daily_tasks.original_task_id -> tasks.id  
ALTER TABLE daily_tasks 
ADD CONSTRAINT fk_daily_tasks_original_task_id 
FOREIGN KEY (original_task_id) REFERENCES tasks(id) ON DELETE CASCADE;

-- Add foreign key constraint for daily_tasks.task_id -> tasks.id
ALTER TABLE daily_tasks 
ADD CONSTRAINT fk_daily_tasks_task_id 
FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE;

-- Add foreign key constraint for task_progress_history.task_id -> tasks.id
ALTER TABLE task_progress_history 
ADD CONSTRAINT fk_task_progress_history_task_id 
FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE;

-- Add foreign key constraint for task_history.task_id -> tasks.id
ALTER TABLE task_history 
ADD CONSTRAINT fk_task_history_task_id 
FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE;