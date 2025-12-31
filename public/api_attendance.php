<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../app/config/database.php';
require_once '../app/models/Attendance.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $attendance = new Attendance($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action']) || !isset($input['user_id'])) {
            throw new Exception('Missing required parameters');
        }
        
        $user_id = $input['user_id'];
        $action = $input['action'];
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        
        if ($action === 'clock_in') {
            $result = $attendance->clockIn($user_id, $latitude, $longitude);
        } elseif ($action === 'clock_out') {
            $result = $attendance->clockOut($user_id, $latitude, $longitude);
        } else {
            throw new Exception('Invalid action');
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
