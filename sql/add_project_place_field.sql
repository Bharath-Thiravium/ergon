-- Add place field to projects table
ALTER TABLE projects ADD COLUMN place VARCHAR(255) NULL AFTER description;

-- Update existing projects with default place names if they have coordinates
UPDATE projects 
SET place = CONCAT('Location (', ROUND(latitude, 4), ', ', ROUND(longitude, 4), ')') 
WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND (place IS NULL OR place = '');