<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Find Nelson Raj's user ID
$stmt = $db->prepare("SELECT id, name FROM users WHERE name LIKE '%Nelson%' OR name LIKE '%Raj%'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== USERS MATCHING NELSON/RAJ ===\n";
foreach ($users as $user) {
    echo "ID: " . $user['id'] . ", Name: " . $user['name'] . "\n";
}
echo "\n";

// Get latest attendance record
$stmt = $db->prepare("SELECT * FROM attendance ORDER BY check_in DESC LIMIT 1");
$stmt->execute();
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== LATEST ATTENDANCE RECORD ===\n";
if ($attendance) {
    // Get user name
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$attendance['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User: " . ($user['name'] ?? 'Unknown') . " (ID: " . $attendance['user_id'] . ")\n";
    echo "Clock-in Time: " . ($attendance['check_in'] ?: 'N/A') . "\n";
    echo "Clock-in Coordinates: " . ($attendance['latitude'] ?: 'N/A') . ", " . ($attendance['longitude'] ?: 'N/A') . "\n";
    echo "Assigned Project ID: " . ($attendance['project_id'] ?: 'None') . "\n";
    echo "Location Name: " . ($attendance['location_name'] ?: 'N/A') . "\n";
} else {
    echo "No attendance record found\n";
}
echo "\n";

// Get all active projects with GPS coordinates
$stmt = $db->prepare("SELECT id, name, latitude, longitude, checkin_radius, location_title, place FROM projects WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'active'");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== PROJECT LOCATIONS ===\n";
foreach ($projects as $project) {
    echo "Project ID: " . $project['id'] . "\n";
    echo "Name: " . $project['name'] . "\n";
    echo "Place: " . ($project['place'] ?: 'N/A') . "\n";
    echo "Location Title: " . ($project['location_title'] ?: 'N/A') . "\n";
    echo "Coordinates: " . $project['latitude'] . ", " . $project['longitude'] . "\n";
    echo "Allowed Radius: " . $project['checkin_radius'] . "m\n";
    
    if ($attendance && $attendance['latitude'] && $attendance['longitude']) {
        $distance = calculateDistance($attendance['latitude'], $attendance['longitude'], $project['latitude'], $project['longitude']);
        $withinRadius = $distance <= $project['checkin_radius'];
        echo "Distance from Clock-in: " . round($distance, 2) . "m\n";
        echo "Within Radius: " . ($withinRadius ? 'YES' : 'NO') . "\n";
    } else {
        echo "Distance from Clock-in: Cannot calculate (no GPS data)\n";
        echo "Within Radius: NO\n";
    }
    echo "---\n";
}

// Check if there's a Market Research project (likely project 15)
echo "\n=== MARKET RESEARCH PROJECT CHECK ===\n";
$stmt = $db->prepare("SELECT id, name, place, latitude, longitude, checkin_radius FROM projects WHERE name LIKE '%Market%' OR name LIKE '%Research%' OR place LIKE '%Madurai%'");
$stmt->execute();
$marketProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($marketProjects) {
    foreach ($marketProjects as $project) {
        echo "Found Market Research Project:\n";
        echo "ID: " . $project['id'] . "\n";
        echo "Name: " . $project['name'] . "\n";
        echo "Place: " . ($project['place'] ?: 'N/A') . "\n";
        echo "Coordinates: " . $project['latitude'] . ", " . $project['longitude'] . "\n";
        echo "Radius: " . $project['checkin_radius'] . "m\n";
        echo "---\n";
    }
} else {
    echo "No Market Research project found\n";
}

// Check recent attendance records
echo "\n=== RECENT ATTENDANCE RECORDS ===\n";
$stmt = $db->prepare("SELECT a.id, a.user_id, u.name, a.check_in, a.check_out, a.latitude, a.longitude, a.project_id, a.location_name FROM attendance a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.check_in DESC LIMIT 5");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($records as $record) {
    echo "User: " . ($record['name'] ?? 'Unknown') . " (ID: " . $record['user_id'] . ")\n";
    echo "Attendance ID: " . $record['id'] . "\n";
    echo "Check-in: " . ($record['check_in'] ?: 'N/A') . "\n";
    echo "Check-out: " . ($record['check_out'] ?: 'Not clocked out') . "\n";
    echo "GPS: " . ($record['latitude'] ?: 'N/A') . ", " . ($record['longitude'] ?: 'N/A') . "\n";
    echo "Project ID: " . ($record['project_id'] ?: 'None') . "\n";
    echo "Location: " . ($record['location_name'] ?: 'N/A') . "\n";
    echo "---\n";
}

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    if (!$lat1 || !$lng1 || !$lat2 || !$lng2) return 0;
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}
?>
