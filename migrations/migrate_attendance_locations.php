<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting attendance location migration...\n";
    
    // Add new columns if they don't exist (safe operation)
    $alterQueries = [
        "ALTER TABLE attendance ADD COLUMN location_type VARCHAR(50) DEFAULT 'office'",
        "ALTER TABLE attendance ADD COLUMN location_title VARCHAR(255) DEFAULT 'Main Office'", 
        "ALTER TABLE attendance ADD COLUMN location_radius INT DEFAULT 50"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "✓ Added column successfully\n";
        } catch (Exception $e) {
            echo "- Column already exists or error: " . $e->getMessage() . "\n";
        }
    }
    
    // Update existing records with default values (safe operation)
    $updateQuery = "
        UPDATE attendance 
        SET 
            location_type = CASE 
                WHEN project_id IS NOT NULL THEN 'project'
                ELSE 'office'
            END,
            location_title = CASE 
                WHEN project_id IS NOT NULL THEN 
                    COALESCE(
                        (SELECT COALESCE(location_title, CONCAT(name, ' Site')) FROM projects WHERE id = attendance.project_id),
                        'Project Site'
                    )
                ELSE COALESCE(location_name, 'Main Office')
            END,
            location_radius = CASE 
                WHEN project_id IS NOT NULL THEN 
                    COALESCE((SELECT checkin_radius FROM projects WHERE id = attendance.project_id), 100)
                ELSE 50
            END
        WHERE location_type IS NULL OR location_title IS NULL OR location_radius IS NULL
    ";
    
    $stmt = $db->prepare($updateQuery);
    $result = $stmt->execute();
    $affected = $stmt->rowCount();
    
    echo "✓ Updated {$affected} attendance records with location data\n";
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
