<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Attendance.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request data');
    }
    
    $attendance = new Attendance();
    $userId = $_SESSION['user_id'];
    $action = $input['action'];
    
    if ($action === 'clock_in') {
        $result = $attendance->clockIn($userId, [
            'latitude' => $input['latitude'] ?? 0,
            'longitude' => $input['longitude'] ?? 0,
            'location_name' => $input['location_name'] ?? 'Office'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clocked in successfully',
            'data' => $result
        ]);
        
    } elseif ($action === 'clock_out') {
        $result = $attendance->clockOut($userId, [
            'latitude' => $input['latitude'] ?? 0,
            'longitude' => $input['longitude'] ?? 0,
            'location_name' => $input['location_name'] ?? 'Office'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clocked out successfully',
            'data' => $result
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
