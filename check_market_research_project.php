<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== MARKET RESEARCH PROJECT CHECK ===\n\n";
    
    // Check Market Research project details
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = 15");
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "Market Research Project Found:\n";
        echo "  ID: {$project['id']}\n";
        echo "  Name: {$project['name']}\n";
        echo "  Status: {$project['status']}\n";
        echo "  Latitude: {$project['latitude']}\n";
        echo "  Longitude: {$project['longitude']}\n";
        echo "  Radius: {$project['checkin_radius']}m\n";
        echo "  Place: {$project['place']}\n\n";
        
        // Test GPS matching with recent attendance coordinates
        $testCoords = [
            ['name' => 'Record 29', 'lat' => 9.98139350, 'lng' => 78.14315794],
            ['name' => 'Record 31', 'lat' => 9.98144575, 'lng' => 78.14310141]
        ];
        
        foreach ($testCoords as $coord) {
            $distance = calculateDistance($coord['lat'], $coord['lng'], $project['latitude'], $project['longitude']);
            $withinRadius = $distance <= $project['checkin_radius'];
            $status = $withinRadius ? "✅ SHOULD GET PROJECT ID 15" : "❌ NO MATCH";
            
            echo "{$coord['name']}: Distance = " . round($distance, 2) . "m, Status: {$status}\n";
        }
    } else {
        echo "❌ Market Research project (ID: 15) not found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}
?>
