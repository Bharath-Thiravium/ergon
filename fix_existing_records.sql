-- Fix existing attendance records that have project_id assigned without GPS coordinates
-- This will set project_id to NULL for records that don't have valid GPS coordinates

-- First, let's see what we're dealing with
SELECT 'Records with project_id but no GPS coordinates:' as description;
SELECT COUNT(*) as count, project_id
FROM attendance 
WHERE project_id IS NOT NULL
AND (latitude IS NULL OR latitude = 0) 
AND (longitude IS NULL OR longitude = 0)
GROUP BY project_id;

-- Update records to remove project_id where there are no GPS coordinates
UPDATE attendance 
SET project_id = NULL 
WHERE project_id IS NOT NULL
AND (latitude IS NULL OR latitude = 0) 
AND (longitude IS NULL OR longitude = 0);

-- Show the result
SELECT 'After fix - Records with project_id but no GPS coordinates:' as description;
SELECT COUNT(*) as count, project_id
FROM attendance 
WHERE project_id IS NOT NULL
AND (latitude IS NULL OR latitude = 0) 
AND (longitude IS NULL OR longitude = 0)
GROUP BY project_id;

-- Verify the fix worked
SELECT 'Total records with NULL project_id (should be higher now):' as description;
SELECT COUNT(*) as count FROM attendance WHERE project_id IS NULL;