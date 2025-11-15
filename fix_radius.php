<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Update attendance radius to a more reasonable value
    $stmt = $db->prepare("UPDATE settings SET attendance_radius = ?");
    $result = $stmt->execute([200]); // 200 meters
    
    if ($result) {
        echo "Updated attendance radius to 200 meters\n";
    } else {
        echo "Failed to update radius\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>