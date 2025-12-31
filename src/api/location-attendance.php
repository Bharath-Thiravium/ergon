<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    require_once __DIR__ . '/../../app/config/database.php';
    $db = Database::connect();
    
    $user_lat = floatval($_POST['latitude'] ?? 0);
    $user_lng = floatval($_POST['longitude'] ?? 0);
    
    if ($user_lat == 0 || $user_lng == 0) {
        echo json_encode(['success' => true, 'within_range' => true, 'location_name' => 'Remote Location']);
        exit;
    }
    
    // Check all active projects
    $stmt = $db->prepare("SELECT id, name, latitude, longitude, checkin_radius, location_title FROM projects WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'active'");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        $distance = calculateDistance($user_lat, $user_lng, $project['latitude'], $project['longitude']);
        if ($distance <= $project['checkin_radius']) {
            echo json_encode([
                'success' => true,
                'within_range' => true,
                'distance' => round($distance, 2),
                'allowed_radius' => $project['checkin_radius'],
                'project_name' => $project['location_title'] ?: $project['name'],
                'project_id' => $project['id']
            ]);
            exit;
        }
    }
    
    // Check settings location
    $stmt = $db->prepare("SELECT base_location_lat, base_location_lng, attendance_radius, location_title FROM settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings && $settings['base_location_lat'] != 0 && $settings['base_location_lng'] != 0) {
        $distance = calculateDistance($user_lat, $user_lng, $settings['base_location_lat'], $settings['base_location_lng']);
        if ($distance <= $settings['attendance_radius']) {
            echo json_encode([
                'success' => true,
                'within_range' => true,
                'distance' => round($distance, 2),
                'allowed_radius' => $settings['attendance_radius'],
                'project_name' => $settings['location_title'] ?: 'Main Office'
                // No project_id assigned for office location - only assign when GPS matches specific project
            ]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'within_range' => false, 'error' => 'Outside allowed check-in area']);
    
} catch (Exception $e) {
    error_log('Location attendance error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // Earth radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}
?>
