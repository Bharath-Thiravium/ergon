<?php
session_start();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    require_once 'app/models/Attendance.php';
    $attendanceModel = new Attendance();
    
    $action = $input['action'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if ($action === 'clock_in') {
        $result = $attendanceModel->checkIn(
            $userId,
            $input['latitude'] ?? 0,
            $input['longitude'] ?? 0,
            $input['location_name'] ?? 'Office'
        );
        echo json_encode([
            'success' => $result, 
            'message' => $result ? 'Clocked in successfully' : 'Already clocked in or error occurred',
            'status' => 'in'
        ]);
    } elseif ($action === 'clock_out') {
        $result = $attendanceModel->checkOut($userId);
        echo json_encode([
            'success' => $result, 
            'message' => $result ? 'Clocked out successfully' : 'Not clocked in or error occurred',
            'status' => 'out'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>