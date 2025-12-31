<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/LocationHelper.php';

try {
    $db = Database::connect();
    
    // Get all active project locations
    $stmt = $db->query("
        SELECT 
            id,
            name,
            COALESCE(location_title, CONCAT(name, ' Site')) as location_title,
            latitude,
            longitude,
            checkin_radius,
            'project' as type
        FROM projects 
        WHERE latitude IS NOT NULL 
        AND longitude IS NOT NULL 
        AND status = 'active'
        ORDER BY name
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get office location
    $settings = LocationHelper::getOfficeSettings($db);
    $office = null;
    if ($settings['base_location_lat'] != 0 && $settings['base_location_lng'] != 0) {
        $office = [
            'id' => 0,
            'name' => 'Main Office',
            'location_title' => $settings['location_title'] ?? 'Main Office',
            'latitude' => $settings['base_location_lat'],
            'longitude' => $settings['base_location_lng'],
            'checkin_radius' => $settings['attendance_radius'],
            'type' => 'office'
        ];
    }
    
    $response = [
        'success' => true,
        'projects' => $projects,
        'office' => $office,
        'total_locations' => count($projects) + ($office ? 1 : 0)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Project locations API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch project locations'
    ]);
}
?>
