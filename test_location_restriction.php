<?php
// Test script to verify location restriction functionality
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/LocationHelper.php';

echo "<h2>Location Restriction Test</h2>";

try {
    $db = Database::connect();
    
    // Get office settings
    $settings = LocationHelper::getOfficeSettings($db);
    echo "<h3>Office Settings:</h3>";
    echo "Latitude: " . ($settings['base_location_lat'] ?? 'Not set') . "<br>";
    echo "Longitude: " . ($settings['base_location_lng'] ?? 'Not set') . "<br>";
    echo "Radius: " . ($settings['attendance_radius'] ?? 'Not set') . " meters<br><br>";
    
    // Test cases
    $testCases = [
        [
            'name' => 'Same location (should pass)',
            'lat' => $settings['base_location_lat'],
            'lng' => $settings['base_location_lng']
        ],
        [
            'name' => 'Nearby location (should pass if within radius)',
            'lat' => $settings['base_location_lat'] + 0.001, // ~111 meters
            'lng' => $settings['base_location_lng'] + 0.001
        ],
        [
            'name' => 'Far location (should fail)',
            'lat' => $settings['base_location_lat'] + 0.01, // ~1.1 km
            'lng' => $settings['base_location_lng'] + 0.01
        ]
    ];
    
    echo "<h3>Test Results:</h3>";
    foreach ($testCases as $test) {
        $result = LocationHelper::isWithinAttendanceRadius($test['lat'], $test['lng'], $settings);
        
        echo "<div style='margin-bottom: 15px; padding: 10px; border: 1px solid " . 
             ($result['allowed'] ? '#22c55e' : '#ef4444') . "; border-radius: 5px;'>";
        echo "<strong>" . $test['name'] . "</strong><br>";
        echo "Test coordinates: " . $test['lat'] . ", " . $test['lng'] . "<br>";
        echo "Distance: " . ($result['distance'] ?? 'N/A') . " meters<br>";
        echo "Allowed: " . ($result['allowed'] ? 'YES' : 'NO') . "<br>";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "<br>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>