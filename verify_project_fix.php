<?php
/**
 * Verify Project Assignment Fix
 * Check if automatic project assignment has been properly fixed
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== PROJECT ASSIGNMENT FIX VERIFICATION ===\n\n";
    
    // 1. Check recent attendance records without GPS coordinates
    echo "1. Recent attendance records without GPS coordinates:\n";
    $stmt = $db->prepare("
        SELECT id, user_id, project_id, check_in, latitude, longitude 
        FROM attendance 
        WHERE (latitude IS NULL OR latitude = 0) 
        AND (longitude IS NULL OR longitude = 0)
        AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY check_in DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        $projectStatus = $record['project_id'] ? "ASSIGNED (ID: {$record['project_id']})" : "NULL (CORRECT)";
        echo "  ID: {$record['id']}, User: {$record['user_id']}, Project: {$projectStatus}, Date: {$record['check_in']}\n";
    }
    
    // 2. Check if any records still have project_id = 13 or 15 without GPS
    echo "\n2. Records with project_id 13 or 15 but no GPS coordinates:\n";
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, project_id
        FROM attendance 
        WHERE project_id IN (13, 15)
        AND (latitude IS NULL OR latitude = 0) 
        AND (longitude IS NULL OR longitude = 0)
        AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY project_id
    ");
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($counts)) {
        echo "  ✅ No records found with automatic project assignment (GOOD)\n";
    } else {
        foreach ($counts as $count) {
            echo "  ❌ Found {$count['count']} records with project_id {$count['project_id']} but no GPS\n";
        }
    }
    
    // 3. Check project locations configuration
    echo "\n3. Active project locations:\n";
    $stmt = $db->prepare("
        SELECT id, name, place, latitude, longitude, checkin_radius 
        FROM projects 
        WHERE status = 'active' 
        AND latitude IS NOT NULL 
        AND longitude IS NOT NULL
        ORDER BY id
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "  ID: {$project['id']}, Name: {$project['name']}, Location: ({$project['latitude']}, {$project['longitude']}), Radius: {$project['checkin_radius']}m\n";
    }
    
    // 4. Test GPS matching logic
    echo "\n4. Testing GPS matching logic:\n";
    
    // Test coordinates that should match Market Research project
    $testLat = 9.9816;
    $testLng = 78.1434;
    
    foreach ($projects as $project) {
        if ($project['latitude'] != 0 && $project['longitude'] != 0) {
            $distance = calculateDistance($testLat, $testLng, $project['latitude'], $project['longitude']);
            $withinRadius = $distance <= $project['checkin_radius'];
            $status = $withinRadius ? "✅ MATCH" : "❌ NO MATCH";
            echo "  Test coords vs {$project['name']}: {$distance}m distance, {$status}\n";
        }
    }
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // Earth radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // Distance in meters
}
?>
