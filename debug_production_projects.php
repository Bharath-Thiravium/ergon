<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== PRODUCTION PROJECT DEBUG ===\n\n";
    
    // 1. Check all projects
    echo "1. ALL PROJECTS:\n";
    $stmt = $db->prepare("SELECT id, name, status, latitude, longitude, checkin_radius, place FROM projects ORDER BY id");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "  ID: {$project['id']}, Name: {$project['name']}, Status: {$project['status']}\n";
        echo "     Location: ({$project['latitude']}, {$project['longitude']}) - Radius: {$project['checkin_radius']}m\n";
        echo "     Place: {$project['place']}\n\n";
    }
    
    // 2. Check recent attendance with project_id
    echo "2. RECENT ATTENDANCE WITH PROJECT_ID:\n";
    $stmt = $db->prepare("
        SELECT a.id, a.user_id, a.project_id, a.latitude, a.longitude, a.check_in, p.name as project_name
        FROM attendance a 
        LEFT JOIN projects p ON a.project_id = p.id
        WHERE a.project_id IS NOT NULL 
        AND DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        ORDER BY a.check_in DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "  No recent attendance records with project_id found\n\n";
    } else {
        foreach ($records as $record) {
            echo "  ID: {$record['id']}, User: {$record['user_id']}, Project: {$record['project_id']} ({$record['project_name']})\n";
            echo "     GPS: ({$record['latitude']}, {$record['longitude']}), Date: {$record['check_in']}\n\n";
        }
    }
    
    // 3. Check recent attendance without project_id but with GPS
    echo "3. RECENT ATTENDANCE WITHOUT PROJECT_ID (GPS AVAILABLE):\n";
    $stmt = $db->prepare("
        SELECT id, user_id, project_id, latitude, longitude, check_in
        FROM attendance 
        WHERE project_id IS NULL 
        AND latitude IS NOT NULL 
        AND longitude IS NOT NULL
        AND latitude != 0 
        AND longitude != 0
        AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        ORDER BY check_in DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        echo "  ID: {$record['id']}, User: {$record['user_id']}, GPS: ({$record['latitude']}, {$record['longitude']})\n";
        echo "     Date: {$record['check_in']}\n";
        
        // Test GPS matching for each active project
        foreach ($projects as $project) {
            if ($project['latitude'] && $project['longitude'] && $project['status'] === 'active') {
                $distance = calculateDistance($record['latitude'], $record['longitude'], $project['latitude'], $project['longitude']);
                $match = $distance <= $project['checkin_radius'] ? "✅ MATCH" : "❌ NO MATCH";
                echo "     vs Project {$project['id']} ({$project['name']}): {$distance}m - {$match}\n";
            }
        }
        echo "\n";
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
