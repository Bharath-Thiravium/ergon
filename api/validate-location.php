<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/LocationHelper.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['latitude']) || !isset($input['longitude'])) {
        echo json_encode(['error' => 'Latitude and longitude are required']);
        exit;
    }
    
    $userLat = floatval($input['latitude']);
    $userLng = floatval($input['longitude']);
    
    if ($userLat == 0 || $userLng == 0) {
        echo json_encode([
            'allowed' => false,
            'error' => 'Invalid GPS coordinates. Please ensure location services are enabled.'
        ]);
        exit;
    }
    
    $db = Database::connect();
    $locations = LocationHelper::getAllowedLocations($db);
    
    if (empty($locations)) {
        echo json_encode([
            'allowed' => false,
            'error' => 'No attendance locations have been configured by your administrator.'
        ]);
        exit;
    }
    
    $validation = LocationHelper::validateMultipleLocations($userLat, $userLng, $locations);
    
    if ($validation['allowed']) {
        echo json_encode([
            'allowed' => true,
            'location_info' => [
                'title' => $validation['location']['name'],
                'type' => $validation['location']['type'],
                'radius' => $validation['location']['radius']
            ],
            'distance' => $validation['distance']
        ]);
    } else {
        echo json_encode([
            'allowed' => false,
            'error' => $validation['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Location validation API error: ' . $e->getMessage());
    echo json_encode([
        'allowed' => false,
        'error' => 'Server error occurred while validating location'
    ]);
}
?>
