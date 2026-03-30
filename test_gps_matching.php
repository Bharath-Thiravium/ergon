<?php
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // Earth radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // Distance in meters
}

// Market Research project coordinates
$projectLat = 9.98156700;
$projectLng = 78.14340000;
$projectRadius = 1000; // meters

// Test attendance record coordinates
$testCoords = [
    ['name' => 'Record 29', 'lat' => 9.98139350, 'lng' => 78.14315794],
    ['name' => 'Record 31', 'lat' => 9.98144575, 'lng' => 78.14310141]
];

echo "=== GPS MATCHING TEST ===\n";
echo "Market Research Project: ({$projectLat}, {$projectLng}) - Radius: {$projectRadius}m\n\n";

foreach ($testCoords as $coord) {
    $distance = calculateDistance($coord['lat'], $coord['lng'], $projectLat, $projectLng);
    $withinRadius = $distance <= $projectRadius;
    $status = $withinRadius ? "✅ SHOULD MATCH" : "❌ NO MATCH";
    
    echo "{$coord['name']}: ({$coord['lat']}, {$coord['lng']})\n";
    echo "  Distance: " . round($distance, 2) . "m\n";
    echo "  Status: {$status}\n\n";
}
?>
