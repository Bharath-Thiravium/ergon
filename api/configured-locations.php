<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/LocationHelper.php';

try {
    $db = Database::connect();
    $locations = LocationHelper::getAllowedLocations($db);
    
    echo json_encode($locations);
    
} catch (Exception $e) {
    error_log('Configured locations API error: ' . $e->getMessage());
    echo json_encode([]);
}
?>
