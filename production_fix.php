<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Production Attendance Fix<br><br>";
    
    // 1. Update projects places if empty
    $stmt = $db->prepare("UPDATE projects SET place = CONCAT(name, ' Site') WHERE (place = '' OR place IS NULL) AND status = 'active'");
    $stmt->execute();
    echo "✅ Projects updated: " . $stmt->rowCount() . " rows<br>";
    
    // 2. Get active project
    $stmt = $db->prepare("SELECT id FROM projects WHERE status = 'active' ORDER BY name ASC LIMIT 1");
    $stmt->execute();
    $projectId = $stmt->fetchColumn();
    
    if ($projectId) {
        // 3. Update attendance records without project_id
        $stmt = $db->prepare("UPDATE attendance SET project_id = ? WHERE project_id IS NULL OR project_id = 0 OR project_id = ''");
        $stmt->execute([$projectId]);
        echo "✅ Attendance linked: " . $stmt->rowCount() . " rows<br>";
        
        // 4. Add missing columns if needed
        try {
            $db->exec("ALTER TABLE attendance ADD COLUMN location_display VARCHAR(255) NULL");
            echo "✅ Added location_display column<br>";
        } catch (Exception $e) {}
        
        try {
            $db->exec("ALTER TABLE attendance ADD COLUMN project_name VARCHAR(255) NULL");
            echo "✅ Added project_name column<br>";
        } catch (Exception $e) {}
        
        // 5. Clear any existing hardcoded values
        $stmt = $db->prepare("UPDATE attendance SET location_display = NULL, project_name = NULL WHERE location_display IS NOT NULL OR project_name IS NOT NULL");
        $stmt->execute();
        echo "✅ Cleared old data: " . $stmt->rowCount() . " rows<br>";
    }
    
    echo "<br><strong>✅ Production fix complete!</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
