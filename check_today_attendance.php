<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Check today's attendance for Nelson (user_id = 37)
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = 37 AND DATE(check_in) = CURDATE()");
$stmt->execute();
$todayRecord = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== TODAY'S ATTENDANCE FOR NELSON (ID: 37) ===\n";
if ($todayRecord) {
    echo "Attendance ID: " . $todayRecord['id'] . "\n";
    echo "Check-in: " . $todayRecord['check_in'] . "\n";
    echo "Check-out: " . ($todayRecord['check_out'] ?: 'Not clocked out') . "\n";
    echo "GPS Coordinates: " . ($todayRecord['latitude'] ?: 'NULL') . ", " . ($todayRecord['longitude'] ?: 'NULL') . "\n";
    echo "Project ID: " . ($todayRecord['project_id'] ?: 'NULL') . "\n";
    echo "Location Name: " . ($todayRecord['location_name'] ?: 'NULL') . "\n";
    echo "Status: " . ($todayRecord['status'] ?: 'NULL') . "\n";
    echo "Manual Entry: " . ($todayRecord['manual_entry'] ? 'YES' : 'NO') . "\n";
    echo "IP Address: " . ($todayRecord['ip_address'] ?: 'NULL') . "\n";
    echo "Device Info: " . ($todayRecord['device_info'] ?: 'NULL') . "\n";
} else {
    echo "No attendance record found for today\n";
}

// Check all projects to see if Market Research exists with different name
echo "\n=== ALL PROJECTS ===\n";
$stmt = $db->prepare("SELECT id, name, place, latitude, longitude, checkin_radius, status FROM projects ORDER BY id");
$stmt->execute();
$allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allProjects as $project) {
    echo "ID: " . $project['id'] . " | Name: " . $project['name'] . " | Place: " . ($project['place'] ?: 'N/A') . " | Status: " . $project['status'] . "\n";
    if ($project['latitude'] && $project['longitude']) {
        echo "  GPS: " . $project['latitude'] . ", " . $project['longitude'] . " | Radius: " . $project['checkin_radius'] . "m\n";
    }
    echo "---\n";
}
?>
