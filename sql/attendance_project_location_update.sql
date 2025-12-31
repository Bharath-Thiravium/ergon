-- Add new columns to attendance table for project-based location tracking
ALTER TABLE attendance ADD COLUMN location_display VARCHAR(255) NULL AFTER location_name;
ALTER TABLE attendance ADD COLUMN project_name VARCHAR(255) NULL AFTER project_id;

-- Update existing records with default values
UPDATE attendance SET 
    location_display = CASE 
        WHEN project_id IS NOT NULL THEN 
            COALESCE((SELECT CONCAT(name, ' - ', COALESCE(place, 'Site')) FROM projects WHERE id = attendance.project_id), 'Project Site')
        ELSE 
            COALESCE((SELECT company_name FROM settings LIMIT 1), 'Company Office')
    END,
    project_name = CASE 
        WHEN project_id IS NOT NULL THEN 
            (SELECT name FROM projects WHERE id = attendance.project_id)
        ELSE NULL
    END
WHERE location_display IS NULL OR project_name IS NULL;