<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Fix the latest records with NULL project_id
    $stmt = $db->prepare("UPDATE attendance SET project_id = 14 WHERE project_id IS NULL AND DATE(check_in) = '2025-12-15'");
    $stmt->execute();
    echo "✅ Fixed latest records: " . $stmt->rowCount() . " rows<br>";
    
    // Verify the fix
    $stmt = $db->prepare("SELECT id, user_id, project_id FROM attendance WHERE DATE(check_in) = '2025-12-15'");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        echo "Record {$record['id']}: User {$record['user_id']} → Project ID {$record['project_id']}<br>";
    }
    
    echo "<br>✅ All today's records now have project_id = 14";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
