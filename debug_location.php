<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check settings data
    $stmt = $db->query("SELECT base_location_lat, base_location_lng, attendance_radius FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Office Location Settings:\n";
    echo "Latitude: " . ($settings['base_location_lat'] ?? 'NULL') . "\n";
    echo "Longitude: " . ($settings['base_location_lng'] ?? 'NULL') . "\n";
    echo "Radius: " . ($settings['attendance_radius'] ?? 'NULL') . "m\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>