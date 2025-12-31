<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Fix Nelson's attendance record (User 57) - assign to default project
    $stmt = $db->prepare("UPDATE attendance SET project_id = 14 WHERE user_id = 57 AND (project_id IS NULL OR project_id = 0 OR project_id = '')");
    $stmt->execute();
    
    echo "✅ Fixed Nelson's attendance: " . $stmt->rowCount() . " records updated<br>";
    
    // Verify the fix
    $stmt = $db->prepare("SELECT a.user_id, a.project_id, p.name, p.place FROM attendance a LEFT JOIN projects p ON a.project_id = p.id WHERE a.user_id = 57 AND a.check_in LIKE '2025-12-15%'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Nelson's record: Project '{$result['name']}' at '{$result['place']}'<br>";
    }
    
    echo "<br>✅ Nelson should now show proper location/project!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
