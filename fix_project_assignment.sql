-- Fix to prevent automatic project assignment
-- Remove any default project assignment logic

-- Update existing attendance records that have project_id but no GPS coordinates
UPDATE attendance 
SET project_id = NULL 
WHERE project_id IS NOT NULL 
AND (latitude IS NULL OR longitude IS NULL OR latitude = 0 OR longitude = 0);

-- Ensure no default value for project_id column
ALTER TABLE attendance ALTER COLUMN project_id DROP DEFAULT;